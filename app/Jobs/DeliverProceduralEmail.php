<?php

namespace App\Jobs;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\CommunicationDeliveryService;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class DeliverProceduralEmail implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 120;

    public int $retryUntilTimestamp;

    public function __construct(
        public readonly int $communicationDeliveryId,
        public readonly int $municipalityId,
        ?int $retryUntilTimestamp = null,
    ) {
        $this->retryUntilTimestamp = $retryUntilTimestamp
            ?? now()->addHours((int) config(
                'mvhab.procedural_notifications.retry_hours',
                12,
            ))->getTimestamp();
    }

    public function uniqueId(): string
    {
        return 'procedural-email:'.$this->communicationDeliveryId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        $configured = config(
            'mvhab.procedural_notifications.backoff',
            [60, 300, 900, 3600],
        );

        return array_values(array_map(
            static fn (mixed $seconds): int => max(1, (int) $seconds),
            is_array($configured) ? $configured : [60, 300, 900, 3600],
        ));
    }

    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::createFromTimestampUTC(
            $this->retryUntilTimestamp,
        );
    }

    public function handle(
        CommunicationDeliveryService $deliveries,
    ): void {
        $delivery = CommunicationDelivery::query()
            ->findOrFail($this->communicationDeliveryId);

        $result = $deliveries->executeQueued(
            delivery: $delivery,
            actor: null,
            municipalityId: $this->municipalityId,
            permissionContext: 'system.scheduler',
            systemInitiated: true,
            auditMetadata: [
                'job' => self::class,
                'source' => 'procedural_email',
            ],
        );

        if (! in_array($result->status, [
            CommunicationDeliveryStatus::Sent,
            CommunicationDeliveryStatus::Delivered,
            CommunicationDeliveryStatus::Simulated,
        ], true)) {
            if ((string) config('queue.default') === 'sync') {
                return;
            }

            throw new RuntimeException(
                'A entrega processual não atingiu um estado terminal de sucesso.',
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        $delivery = CommunicationDelivery::query()
            ->find($this->communicationDeliveryId);

        if (! $delivery instanceof CommunicationDelivery) {
            return;
        }

        if (! in_array($delivery->status, [
            CommunicationDeliveryStatus::Sent,
            CommunicationDeliveryStatus::Delivered,
            CommunicationDeliveryStatus::Simulated,
        ], true)) {
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => 'Foram esgotadas as tentativas automáticas de entrega do email processual.',
            ])->save();

            $communication = $delivery->communication;

            if ($communication !== null) {
                app(CommunicationDeliveryService::class)
                    ->refreshCommunicationStatus($communication);
            }

            app(AuditLogger::class)->record(
                AuditEvents::UPDATE,
                $delivery,
                'communications',
                'procedural_email_retries_exhausted',
                'Foram esgotadas as tentativas automáticas de entrega do email processual.',
                metadata: [
                    'communication_id' => $delivery->communication_log_id,
                    'delivery_id' => $delivery->id,
                    'exception_class' => $exception::class,
                ],
            );
        }
    }
}
