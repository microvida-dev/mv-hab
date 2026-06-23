<?php

namespace App\Jobs;

use App\Enums\CommunicationReceiptType;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Services\Notifications\CommunicationReceiptService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateCommunicationReceiptJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $communicationId,
        public readonly string $type,
        public readonly ?int $deliveryId = null,
        public readonly ?int $actorId = null,
    ) {}

    public function handle(CommunicationReceiptService $service): void
    {
        $service->generate(
            CommunicationLog::query()->findOrFail($this->communicationId),
            CommunicationReceiptType::from($this->type),
            $this->deliveryId ? CommunicationDelivery::query()->find($this->deliveryId) : null,
            $this->actorId ? User::query()->find($this->actorId) : null,
        );
    }
}
