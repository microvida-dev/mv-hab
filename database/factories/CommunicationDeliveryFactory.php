<?php

namespace Database\Factories;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationDelivery> */
class CommunicationDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'communication_log_id' => CommunicationLog::factory(),
            'channel' => CommunicationChannel::InApp,
            'status' => CommunicationDeliveryStatus::Queued,
            'destination' => null,
            'queued_at' => now(),
        ];
    }
}
