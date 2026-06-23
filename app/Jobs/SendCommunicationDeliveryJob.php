<?php

namespace App\Jobs;

use App\Models\CommunicationDelivery;
use App\Models\User;
use App\Services\Notifications\CommunicationDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCommunicationDeliveryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $deliveryId,
        public readonly ?int $actorId = null,
    ) {}

    public function handle(CommunicationDeliveryService $service): void
    {
        $delivery = CommunicationDelivery::query()->findOrFail($this->deliveryId);
        $service->execute($delivery, $this->actorId ? User::query()->find($this->actorId) : null);
    }
}
