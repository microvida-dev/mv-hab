<?php

namespace App\Services\Notifications;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationStatus;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\AuditTrailService;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Notifications\Channels\EmailChannelService;
use App\Services\Notifications\Channels\InAppChannelService;
use App\Services\Notifications\Channels\InternalChannelService;
use App\Services\Notifications\Channels\PostalChannelService;
use App\Services\Notifications\Channels\SmsChannelService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class CommunicationDeliveryService
{
    public function __construct(
        private readonly InAppChannelService $inApp,
        private readonly InternalChannelService $internal,
        private readonly EmailChannelService $email,
        private readonly SmsChannelService $sms,
        private readonly PostalChannelService $postal,
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly CommunicationMunicipalContextService $context,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly AuditLogger $audit,
        private readonly AuditTrailService $auditTrail,
    ) {}

    public function create(CommunicationLog $communication, CommunicationChannel $channel, ?string $destination = null, ?OfficialNotification $notification = null): CommunicationDelivery
    {
        $delivery = new CommunicationDelivery([
            'communication_log_id' => $communication->id,
            'official_notification_id' => $notification?->id,
            'channel' => $channel,
            'destination' => $destination,
        ]);
        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Queued,
            'queued_at' => now(),
        ])->save();

        return $delivery;
    }

    /**
     * @param  array<string, scalar|null>  $auditMetadata
     */
    public function execute(
        CommunicationDelivery $delivery,
        ?User $actor = null,
        array $auditMetadata = [],
    ): CommunicationDelivery {
        if (
            in_array(
                $delivery->status,
                [
                    CommunicationDeliveryStatus::Sent,
                    CommunicationDeliveryStatus::Delivered,
                    CommunicationDeliveryStatus::Simulated,
                ],
                true,
            )
        ) {
            return $delivery->refresh();
        }

        if (
            ! in_array(
                $delivery->status,
                [
                    CommunicationDeliveryStatus::Pending,
                    CommunicationDeliveryStatus::Queued,
                    CommunicationDeliveryStatus::Failed,
                    CommunicationDeliveryStatus::PendingConfiguration,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'delivery' => 'O estado atual não permite processar a entrega.',
            ]);
        }

        $channel = $delivery->channel;

        $result = match ($channel) {
            CommunicationChannel::InApp => $this->inApp->send($delivery, $actor),
            CommunicationChannel::Internal => $this->internal->send($delivery, $actor),
            CommunicationChannel::Email => $this->email->send($delivery, $actor),
            CommunicationChannel::Sms => $this->sms->send($delivery, $actor),
            CommunicationChannel::Postal => $this->postal->prepare($delivery),
            CommunicationChannel::Document => $this->inApp->send($delivery, $actor),
        };
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);
        $this->refreshCommunicationStatus($communication);
        $this->auditTrail->record(
            eventCode: 'communication_delivery_processed',
            auditable: $result,
            category: AuditEventCategory::Communications,
            severity: $result->status === CommunicationDeliveryStatus::Failed
                ? AuditEventSeverity::Warning
                : AuditEventSeverity::Info,
            description: 'Entrega de comunicação processada.',
            metadata: [
                'channel' => $channel->value,
                'status' => $result->status->value,
                ...$this->safeAuditMetadata($auditMetadata),
            ],
            actor: $actor,
            useAuthenticatedUser: false,
        );

        return $result;
    }

    /**
     * Executa uma entrega enfileirada apenas após revalidar o contexto
     * transportado pelo job.
     *
     * @param  array<string, scalar|null>  $auditMetadata
     */
    public function executeQueued(
        CommunicationDelivery $delivery,
        ?User $actor,
        int $municipalityId,
        string $permissionContext,
        bool $systemInitiated,
        array $auditMetadata = [],
    ): CommunicationDelivery {
        $canonicalMunicipalityId = $this->context->forDelivery($delivery);

        abort_unless(
            $canonicalMunicipalityId !== null
                && $canonicalMunicipalityId === $municipalityId,
            403,
        );

        if ($systemInitiated) {
            abort_unless(
                $permissionContext === 'system.scheduler'
                    && (
                        ! $actor instanceof User
                        || (int) $delivery->communication?->created_by
                            === (int) $actor->id
                    ),
                403,
            );
        } else {
            abort_unless(
                $actor instanceof User
                    && $this->activeActor($actor)
                    && $actor->hasPermission($permissionContext)
                    && (
                        (
                            (int) $actor->municipality_id
                                === $municipalityId
                            && $this->municipalScope
                                ->ownsCommunicationDelivery(
                                    $actor,
                                    $delivery,
                                )
                        )
                        || (
                            $actor->municipality_id === null
                            && $this->platformScope
                                ->hasGlobalScope($actor)
                        )
                    ),
                403,
            );
        }

        return $this->execute(
            $delivery,
            $actor,
            [
                ...$auditMetadata,
                'municipality_id' => $municipalityId,
                'permission_context' => $permissionContext,
                'system_initiated' => $systemInitiated,
            ],
        );
    }

    public function resend(CommunicationDelivery $delivery, User $actor): CommunicationDelivery
    {
        abort_unless(
            $actor->hasPermission('communications.deliveries.resend')
                && $this->municipalScope->ownsCommunicationDelivery(
                    $actor,
                    $delivery,
                ),
            403,
        );

        $status = $delivery->status;

        if (in_array($status, [CommunicationDeliveryStatus::Sent, CommunicationDeliveryStatus::Delivered], true)) {
            throw ValidationException::withMessages(['delivery' => 'Uma entrega concluída não pode ser reenviada sem nova comunicação.']);
        }

        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Queued,
            'failure_reason' => null,
            'failed_at' => null,
            'cancelled_at' => null,
            'queued_at' => now(),
        ])->save();

        $result = $this->execute($delivery, $actor);
        $this->audit->record(
            AuditEvents::UPDATE,
            $result,
            'communications',
            'communication_delivery_resent',
            'Entrega de comunicação reenviada.',
        );

        return $result;
    }

    /** @param array<string, mixed> $data */
    public function registerPostal(CommunicationDelivery $delivery, array $data, User $actor): CommunicationDelivery
    {
        abort_unless(
            $actor->hasPermission('communications.deliveries.postal')
                && $this->municipalScope->ownsCommunicationDelivery(
                    $actor,
                    $delivery,
                ),
            403,
        );

        $channel = $delivery->channel;

        if ($channel !== CommunicationChannel::Postal) {
            throw ValidationException::withMessages(['delivery' => 'A entrega selecionada não é postal.']);
        }

        $result = $this->postal->registerSent($delivery, $data, $actor);
        $communication = $delivery->communication;
        assert($communication instanceof CommunicationLog);
        $this->refreshCommunicationStatus($communication);
        $this->audit->record(
            AuditEvents::UPDATE,
            $result,
            'communications',
            'communication_postal_registered',
            'Expedição postal registada.',
        );

        return $result;
    }

    private function refreshCommunicationStatus(CommunicationLog $communication): void
    {
        $statuses = $communication->deliveries()->pluck('status');

        $status = match (true) {
            $statuses->every(fn ($value) => in_array($value, [CommunicationDeliveryStatus::Sent->value, CommunicationDeliveryStatus::Delivered->value, CommunicationDeliveryStatus::Simulated->value], true)) => CommunicationStatus::Sent,
            $statuses->contains(CommunicationDeliveryStatus::Failed->value) && $statuses->count() === 1 => CommunicationStatus::Failed,
            $statuses->contains(CommunicationDeliveryStatus::Failed->value) => CommunicationStatus::PartiallySent,
            default => CommunicationStatus::Queued,
        };

        $communication->forceFill([
            'status' => $status,
            'sent_at' => $status === CommunicationStatus::Sent ? now() : $communication->sent_at,
            'failed_at' => $status === CommunicationStatus::Failed ? now() : null,
        ])->save();
    }

    private function activeActor(User $actor): bool
    {
        return ($actor->status ?? 'active') === 'active'
            && $actor->deactivated_at === null;
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     * @return array<string, scalar|null>
     */
    private function safeAuditMetadata(array $metadata): array
    {
        return array_intersect_key(
            $metadata,
            array_flip([
                'job',
                'source',
                'correlation_id',
                'municipality_id',
                'permission_context',
                'system_initiated',
            ]),
        );
    }
}
