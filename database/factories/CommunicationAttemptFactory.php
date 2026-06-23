<?php

namespace Database\Factories;

use App\Enums\CommunicationAttemptStatus;
use App\Models\CommunicationAttempt;
use App\Models\CommunicationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommunicationAttempt> */
class CommunicationAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'communication_delivery_id' => CommunicationDelivery::factory(),
            'attempt_number' => 1,
            'status' => CommunicationAttemptStatus::Started,
            'started_at' => now(),
            'created_at' => now(),
        ];
    }
}
