<?php

namespace App\Jobs;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPendingCommunicationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        CommunicationMunicipalContextService $context,
    ): void {
        CommunicationDelivery::query()
            ->whereIn('status', [CommunicationDeliveryStatus::Pending->value, CommunicationDeliveryStatus::Queued->value])
            ->where(function ($query) {
                $query->whereNull('queued_at')->orWhere('queued_at', '<=', now());
            })
            ->with('communication.creator')
            ->chunkById(
                100,
                function ($deliveries) use ($context): void {
                    $deliveries->each(
                        function (CommunicationDelivery $delivery) use (
                            $context,
                        ): void {
                            $municipalityId = $context->forDelivery(
                                $delivery,
                            );

                            if ($municipalityId === null) {
                                return;
                            }

                            SendCommunicationDeliveryJob::dispatch(
                                deliveryId: (int) $delivery->id,
                                actorId: $delivery
                                    ->communication
                                    ?->creator
                                    ?->id,
                                municipalityId: $municipalityId,
                                permissionContext: 'system.scheduler',
                                systemInitiated: true,
                                auditMetadata: [
                                    'source' => 'pending_communications',
                                ],
                            );
                        },
                    );
                },
            );
    }
}
