<?php

namespace Database\Factories;

use App\Models\TenantCommunication;
use App\Models\TenantCommunicationMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantCommunicationMessage> */
class TenantCommunicationMessageFactory extends Factory
{
    protected $model = TenantCommunicationMessage::class;

    public function definition(): array
    {
        return [
            'tenant_communication_id' => TenantCommunication::factory(),
            'user_id' => User::factory(),
            'sender_type' => 'municipality',
            'body' => fake()->paragraph(),
            'visible_to_tenant' => true,
        ];
    }
}
