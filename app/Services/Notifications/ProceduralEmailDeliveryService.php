<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Jobs\DeliverProceduralEmail;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProceduralEmailDeliveryService
{
    public function __construct(
        private readonly CommunicationDeliveryService $deliveries,
        private readonly AuditLogger $audit,
    ) {}

    public function ensureQueued(
        CommunicationLog $communication,
        User $recipient,
        ?OfficialNotification $notification = null,
    ): CommunicationDelivery {
        return DB::transaction(function () use (
            $communication,
            $recipient,
            $notification,
        ): CommunicationDelivery {
            $locked = CommunicationLog::query()
                ->whereKey($communication->id)
                ->lockForUpdate()
                ->firstOrFail();

            $resolvedNotification = $notification
                instanceof OfficialNotification
                    ? $notification
                    : $locked->notifications()->oldest('id')->first();
            $delivery = $locked->deliveries()
                ->where('channel', CommunicationChannel::Email->value)
                ->oldest('id')
                ->first();

            if (! $delivery instanceof CommunicationDelivery) {
                $delivery = $this->deliveries->create(
                    $locked,
                    CommunicationChannel::Email,
                    $recipient->email,
                    $resolvedNotification,
                );
            } elseif (
                ! in_array($delivery->status, [
                    CommunicationDeliveryStatus::Sent,
                    CommunicationDeliveryStatus::Delivered,
                    CommunicationDeliveryStatus::Simulated,
                ], true)
            ) {
                $delivery->forceFill([
                    'destination' => $recipient->email,
                    'official_notification_id' => $resolvedNotification
                        instanceof OfficialNotification
                            ? $resolvedNotification->id
                            : $delivery->official_notification_id,
                ])->save();
            }

            $this->deliveries->refreshCommunicationStatus($locked);

            if (! $this->hasUsableOfficialEmail($recipient)) {
                $delivery->forceFill([
                    'status' => CommunicationDeliveryStatus::Failed,
                    'failed_at' => now(),
                    'failure_reason' => 'O destinatário não possui um email oficial válido e verificado.',
                ])->save();

                $this->deliveries->refreshCommunicationStatus($locked);
                $this->audit->record(
                    AuditEvents::UPDATE,
                    $delivery,
                    'communications',
                    'procedural_email_recipient_invalid',
                    'Entrega processual bloqueada por email oficial inválido ou não verificado.',
                    metadata: [
                        'communication_id' => $locked->id,
                        'recipient_user_id' => $recipient->id,
                    ],
                );

                return $delivery->refresh();
            }

            if ($this->shouldSimulate()) {
                return $this->simulate(
                    $delivery,
                    $locked,
                );
            }

            if (
                ! in_array($delivery->status, [
                    CommunicationDeliveryStatus::Sent,
                    CommunicationDeliveryStatus::Delivered,
                    CommunicationDeliveryStatus::Simulated,
                ], true)
            ) {
                $this->dispatchWithoutBreakingDomainFlow(
                    $delivery,
                    (int) $locked->municipality_id,
                );
            }

            return $delivery->refresh();
        });
    }

    private function hasUsableOfficialEmail(User $recipient): bool
    {
        return filter_var(
            $recipient->email,
            FILTER_VALIDATE_EMAIL,
        ) !== false
            && (
                ! $recipient->hasRole('candidate')
                || $recipient->hasVerifiedEmail()
            );
    }

    private function shouldSimulate(): bool
    {
        return (bool) config(
            'mvhab.procedural_notifications.simulate',
            false,
        )
            || (bool) config(
                'mvhab.municipal_application_demo.enabled',
                false,
            );
    }

    private function simulate(
        CommunicationDelivery $delivery,
        CommunicationLog $communication,
    ): CommunicationDelivery {
        if (
            in_array($delivery->status, [
                CommunicationDeliveryStatus::Sent,
                CommunicationDeliveryStatus::Delivered,
                CommunicationDeliveryStatus::Simulated,
            ], true)
        ) {
            return $delivery->refresh();
        }

        $now = now();

        $delivery->forceFill([
            'status' => CommunicationDeliveryStatus::Simulated,
            'provider' => 'mvhab-demo-simulator',
            'provider_message_id' => null,
            'provider_response' => null,
            'processing_at' => $now,
            'sent_at' => $now,
            'delivered_at' => $now,
            'failed_at' => null,
            'cancelled_at' => null,
            'failure_reason' => null,
        ])->save();

        $this->deliveries->refreshCommunicationStatus(
            $communication,
        );

        $this->audit->record(
            AuditEvents::UPDATE,
            $delivery,
            'communications',
            'procedural_email_simulated',
            'A entrega processual foi simulada sem contacto com um fornecedor externo.',
            metadata: [
                'communication_id' => $communication->id,
                'delivery_id' => $delivery->id,
            ],
        );

        return $delivery->refresh();
    }

    private function dispatchWithoutBreakingDomainFlow(
        CommunicationDelivery $delivery,
        int $municipalityId,
    ): void {
        try {
            DeliverProceduralEmail::dispatch(
                $delivery->id,
                $municipalityId,
            )->onQueue(
                (string) config(
                    'mvhab.procedural_notifications.queue',
                    'communications',
                ),
            )->afterCommit();
        } catch (Throwable $exception) {
            report($exception);

            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => 'Falha ao colocar o email processual na fila de entrega.',
            ])->save();

            $communication = $delivery->communication;

            if ($communication instanceof CommunicationLog) {
                $this->deliveries->refreshCommunicationStatus(
                    $communication,
                );
            }

            $this->audit->record(
                AuditEvents::UPDATE,
                $delivery,
                'communications',
                'procedural_email_queue_dispatch_failed',
                'A colocação da entrega processual na fila falhou sem interromper a operação de domínio.',
                metadata: [
                    'communication_id' => $delivery->communication_log_id,
                    'delivery_id' => $delivery->id,
                ],
            );
        }
    }
}
