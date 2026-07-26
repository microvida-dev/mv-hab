<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationStatus;
use App\Enums\NotificationPriority;
use App\Models\CommunicationLog;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateVersion;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommunicationLogService
{
    public function __construct(
        private readonly CommunicationNumberService $numbers,
        private readonly AuditLogger $audit,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CommunicationMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array{subject?: string|null, title?: string|null, body?: string|null, html_body?: string|null}  $content
     */
    public function create(
        string $eventCode,
        User $recipient,
        array $content,
        ?Model $related = null,
        ?NotificationTemplate $template = null,
        ?NotificationTemplateVersion $version = null,
        ?User $actor = null,
        NotificationPriority $priority = NotificationPriority::Normal,
        bool $official = true,
        bool $requiresAcknowledgement = false,
    ): CommunicationLog {
        $municipalityId = $this->municipalContext->forSources(
            creator: $actor,
            related: $related,
            recipient: $recipient,
            template: $template,
            version: $version,
        );

        if ($municipalityId === null) {
            throw ValidationException::withMessages([
                'recipient_user_id' => 'A origem municipal da comunicação não é coerente com o destinatário e o contexto selecionados.',
            ]);
        }

        $communication = new CommunicationLog([
            'municipality_id' => $municipalityId,
            'event_code' => $eventCode,
            'recipient_user_id' => $recipient->id,
            'recipient_name' => $recipient->name,
            'recipient_email' => $recipient->email,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'notification_template_id' => $template?->id,
            'notification_template_version_id' => $version?->id,
            'subject' => $content['subject'] ?? null,
            'title' => $content['title'] ?? $content['subject'] ?? 'Comunicação oficial',
            'body_snapshot' => $content['body'] ?? '',
            'html_snapshot' => $content['html_body'] ?? null,
            'priority' => $priority,
            'is_official' => $official,
            'requires_acknowledgement' => $requiresAcknowledgement,
        ]);
        $communication->forceFill([
            'communication_number' => $this->numbers->communication(),
            'status' => CommunicationStatus::Queued,
            'created_by' => $actor?->id,
            'queued_at' => now(),
        ])->save();

        $this->audit->record(AuditEvents::CREATE, $communication, 'notifications', 'communication_created', 'Comunicação oficial criada.', metadata: ['event_code' => $eventCode]);

        return $communication;
    }

    public function captureNotification(OfficialNotification $notification, ?User $actor = null): CommunicationLog
    {
        if ($notification->communication_log_id) {
            $communication = $notification->communication;
            assert($communication instanceof CommunicationLog);

            return $communication;
        }

        $user = $notification->user;
        assert($user instanceof User);

        return $this->create(
            eventCode: $notification->event_code ?: $notification->notification_type->value,
            recipient: $user,
            content: [
                'subject' => $notification->subject,
                'title' => $notification->title ?: $notification->subject,
                'body' => $notification->body,
            ],
            related: $notification->notifiable,
            actor: $actor,
            priority: $notification->priority,
            requiresAcknowledgement: (bool) $notification->requires_acknowledgement,
        );
    }

    /** @param array<string, mixed> $data */
    public function storeManual(array $data, User $actor): CommunicationLog
    {
        abort_unless($actor->hasPermission('communications.create'), 403);

        $recipient = $this->municipalScope
            ->users(User::query(), $actor)
            ->whereKey($data['recipient_user_id'] ?? null)
            ->firstOrFail();

        return $this->create(
            eventCode: $data['event_code'],
            recipient: $recipient,
            content: ['subject' => $data['subject'] ?? null, 'title' => $data['title'], 'body' => $data['body']],
            actor: $actor,
            priority: NotificationPriority::from($data['priority']),
            requiresAcknowledgement: (bool) ($data['requires_acknowledgement'] ?? false),
        );
    }

    public function cancel(CommunicationLog $communication, User $actor): CommunicationLog
    {
        abort_unless(
            $actor->hasPermission('communications.cancel')
                && $this->municipalScope->ownsCommunicationLog(
                    $actor,
                    $communication,
                ),
            403,
        );

        if ($communication->status === CommunicationStatus::Sent) {
            throw ValidationException::withMessages(['communication' => 'Uma comunicação enviada não pode ser cancelada.']);
        }

        $result = DB::transaction(function () use ($communication) {
            $communication->deliveries()->update([
                'status' => CommunicationDeliveryStatus::Cancelled->value,
                'cancelled_at' => now(),
            ]);
            $communication->forceFill(['status' => CommunicationStatus::Cancelled, 'cancelled_at' => now()])->save();
            $this->audit->record(AuditEvents::UPDATE, $communication, 'notifications', 'communication_cancelled', 'Comunicação cancelada.');

            return $communication->refresh();
        });

        return $result;
    }

    public function archive(
        CommunicationLog $communication,
        User $actor,
    ): CommunicationLog {
        abort_unless(
            $actor->hasPermission('communications.archive')
                && $this->municipalScope->ownsCommunicationLog(
                    $actor,
                    $communication,
                ),
            403,
        );

        $communication->forceFill(['status' => CommunicationStatus::Archived, 'archived_at' => now()])->save();
        $this->audit->record(
            AuditEvents::UPDATE,
            $communication,
            'notifications',
            'communication_archived',
            'Comunicação arquivada.',
        );

        return $communication->refresh();
    }
}
