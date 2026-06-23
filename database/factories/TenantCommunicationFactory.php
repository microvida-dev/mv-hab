<?php

namespace Database\Factories;

use App\Enums\TenantCommunicationStatus;
use App\Enums\TenantCommunicationVisibility;
use App\Models\TenantCommunication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantCommunication> */
class TenantCommunicationFactory extends Factory
{
    protected $model = TenantCommunication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'summary' => fake()->sentence(10),
            'status' => TenantCommunicationStatus::Open->value,
            'visibility' => TenantCommunicationVisibility::TenantAndMunicipality->value,
            'category' => 'general',
            'priority' => 'normal',
            'opened_at' => now(),
            'last_message_at' => now(),
        ];
    }
}
