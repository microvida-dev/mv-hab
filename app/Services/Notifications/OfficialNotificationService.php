<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationReceiptType;
use App\Enums\NotificationPriority;
use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationStatus;
use App\Enums\OfficialNotificationType;
use App\Models\Application;
use App\Models\CommunicationLog;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OfficialNotificationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CommunicationNumberService $numbers,
        private readonly CommunicationLogService $communications,
        private readonly CommunicationDeliveryService $deliveries,
        private readonly CommunicationReceiptService $receipts,
    ) {}

    public function createInternal(
        User $user,
        OfficialNotificationType $type,
        string $subject,
        string $body,
        ?Model $notifiable = null,
        ?Application $application = null,
        ?User $actor = null,
        OfficialNotificationChannel $channel = OfficialNotificationChannel::CandidateArea,
        bool $requiresAcknowledgement = false,
        ?string $actionUrl = null,
    ): OfficialNotification {
        $communication = $this->communications->create(
            eventCode: $type->value,
            recipient: $user,
            content: ['subject' => $subject, 'title' => $subject, 'body' => $body],
            related: $notifiable,
            actor: $actor,
            priority: NotificationPriority::Normal,
            requiresAcknowledgement: $requiresAcknowledgement,
        );

        return $this->createFromCommunication(
            communication: $communication,
            user: $user,
            type: $type,
            channel: $channel,
            application: $application,
            notifiable: $notifiable,
            actor: $actor,
            actionUrl: $actionUrl,
        );
    }

    public function createFromCommunication(
        CommunicationLog $communication,
        User $user,
        OfficialNotificationType $type = OfficialNotificationType::Other,
        OfficialNotificationChannel $channel = OfficialNotificationChannel::InApp,
        ?Application $application = null,
        ?Model $notifiable = null,
        ?User $actor = null,
        ?string $actionUrl = null,
    ): OfficialNotification {
        $notification = new OfficialNotification([
            'notification_type' => $type,
            'channel' => $channel,
            'subject' => $communication->subject ?: $communication->title,
            'title' => $communication->title,
            'body' => $communication->body_snapshot,
            'action_url' => $actionUrl,
        ]);
        $notification->forceFill([
            'notification_number' => $this->numbers->notification(),
            'user_id' => $user->id,
            'recipient_email' => $user->email,
            'application_id' => $application?->id,
            'communication_log_id' => $communication->id,
            'notifiable_type' => $notifiable?->getMorphClass(),
            'notifiable_id' => $notifiable?->getKey(),
            'event_code' => $communication->event_code,
            'status' => OfficialNotificationStatus::Queued,
            'priority' => $communication->priority->value,
            'requires_acknowledgement' => $communication->requires_acknowledgement,
            'created_by' => $actor?->id,
        ])->save();

        $this->auditLogger->record(
            AuditEvents::CREATE,
            $notification,
            'notifications',
            'official_notification_create',
            'Notificação oficial interna criada.',
            metadata: ['notification_type' => $type->value, 'channel' => $channel->value, 'communication_id' => $communication->id],
        );

        $delivery = $this->deliveries->create(
            $communication,
            $this->communicationChannel($channel),
            $user->email,
            $notification,
        );
        $this->deliveries->execute($delivery, $actor);

        return $notification->refresh();
    }

    /** @param array<string, mixed> $data */
    public function store(array $data, User $actor): OfficialNotification
    {
        $user = User::query()
            ->whereKey($data['user_id'] ?? null)
            ->firstOrFail();
        $application = isset($data['application_id'])
            ? Application::query()->whereKey($data['application_id'])->firstOrFail()
            : null;

        return $this->createInternal(
            user: $user,
            type: OfficialNotificationType::from($data['notification_type']),
            subject: $data['subject'],
            body: $data['body'],
            application: $application,
            actor: $actor,
            channel: OfficialNotificationChannel::from($data['channel'] ?? OfficialNotificationChannel::CandidateArea->value),
        );
    }

    public function markRead(OfficialNotification $notification, User $actor): OfficialNotification
    {
        if ($notification->user_id !== $actor->id && ! $actor->hasPermissionTo('notifications', 'update')) {
            throw ValidationException::withMessages(['notification' => 'Não pode marcar esta notificação como lida.']);
        }

        $notification->forceFill([
            'status' => OfficialNotificationStatus::Read,
            'read_at' => now(),
        ])->save();
        $communication = $notification->communication;
        if ($communication instanceof CommunicationLog) {
            $this->receipts->generate($communication, CommunicationReceiptType::ReadProof, actor: $actor);
        }
        $this->auditLogger->record(AuditEvents::ACCESS, $notification, 'notifications', 'official_notification_read', 'Notificação marcada como lida.');

        return $notification->refresh();
    }

    public function acknowledge(OfficialNotification $notification, User $actor): OfficialNotification
    {
        $this->ensureCanUpdate($notification, $actor);

        if (! $notification->requires_acknowledgement) {
            throw ValidationException::withMessages(['notification' => 'Esta notificação não exige tomada de conhecimento.']);
        }

        $notification->forceFill([
            'status' => OfficialNotificationStatus::Acknowledged,
            'read_at' => $notification->read_at ?? now(),
            'acknowledged_at' => now(),
        ])->save();
        $communication = $notification->communication;
        if ($communication instanceof CommunicationLog) {
            $this->receipts->generate($communication, CommunicationReceiptType::AcknowledgementProof, actor: $actor);
        }
        $this->auditLogger->record(AuditEvents::UPDATE, $notification, 'notifications', 'official_notification_acknowledged', 'Tomada de conhecimento registada.');

        return $notification->refresh();
    }

    public function archive(OfficialNotification $notification, User $actor): OfficialNotification
    {
        $this->ensureCanUpdate($notification, $actor);
        $notification->forceFill(['status' => OfficialNotificationStatus::Archived, 'archived_at' => now()])->save();

        return $notification->refresh();
    }

    public function cancel(OfficialNotification $notification): OfficialNotification
    {
        $notification->forceFill(['status' => OfficialNotificationStatus::Cancelled, 'cancelled_at' => now()])->save();

        return $notification->refresh();
    }

    public function markSent(OfficialNotification $notification): OfficialNotification
    {
        throw ValidationException::withMessages(['notification' => 'O estado de envio é controlado pelas entregas da comunicação.']);
    }

    public function markFailed(OfficialNotification $notification, ?string $reason = null): OfficialNotification
    {
        $notification->forceFill([
            'status' => OfficialNotificationStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        return $notification->refresh();
    }

    private function communicationChannel(OfficialNotificationChannel $channel): CommunicationChannel
    {
        return match ($channel) {
            OfficialNotificationChannel::Internal, OfficialNotificationChannel::Backoffice => CommunicationChannel::Internal,
            OfficialNotificationChannel::Email => CommunicationChannel::Email,
            OfficialNotificationChannel::Sms => CommunicationChannel::Sms,
            OfficialNotificationChannel::Postal => CommunicationChannel::Postal,
            OfficialNotificationChannel::Document => CommunicationChannel::Document,
            default => CommunicationChannel::InApp,
        };
    }

    private function ensureCanUpdate(OfficialNotification $notification, User $actor): void
    {
        if ($notification->user_id !== $actor->id && ! $actor->hasPermissionTo('notifications', 'update')) {
            throw ValidationException::withMessages(['notification' => 'Não pode alterar esta notificação.']);
        }
    }
}
