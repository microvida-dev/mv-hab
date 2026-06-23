<?php

namespace Database\Factories;

use App\Enums\AnonymizationStatus;
use App\Models\AnonymizationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AnonymizationRequest> */
class AnonymizationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_number' => 'ANON-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'user_id' => User::factory(),
            'status' => AnonymizationStatus::Draft->value,
            'anonymization_type' => 'user_profile',
            'reason' => 'Pedido fictício de anonimização para teste.',
            'scope' => ['user.profile'],
        ];
    }
}
