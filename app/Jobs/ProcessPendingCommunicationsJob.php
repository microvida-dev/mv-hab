<?php

namespace App\Jobs;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Services\Notifications\CommunicationDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPendingCommunicationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(CommunicationDeliveryService $service): void
    {
        CommunicationDelivery::query()
            ->whereIn('status', [CommunicationDeliveryStatus::Pending->value, CommunicationDeliveryStatus::Queued->value])
            ->where(function ($query) {
                $query->whereNull('queued_at')->orWhere('queued_at', '<=', now());
            })
            ->chunkById(100, fn ($deliveries) => $deliveries->each(fn (CommunicationDelivery $delivery) => $service->execute($delivery)));
    }
}
