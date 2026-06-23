<?php

namespace Database\Factories;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DataSubjectRequest> */
class DataSubjectRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_number' => 'RGPD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'user_id' => User::factory(),
            'requester_name' => 'Titular Demo',
            'requester_email' => fake()->safeEmail(),
            'request_type' => DataSubjectRequestType::Access->value,
            'status' => DataSubjectRequestStatus::Submitted->value,
            'description' => 'Pedido RGPD fictício para teste.',
            'received_at' => now(),
            'due_at' => now()->addDays(30),
        ];
    }
}
