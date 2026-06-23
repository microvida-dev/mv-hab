<?php

namespace Database\Factories;

use App\Enums\ControlledWithdrawalStatus;
use App\Models\Application;
use App\Models\ControlledWithdrawal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ControlledWithdrawal> */
class ControlledWithdrawalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'status' => ControlledWithdrawalStatus::PendingConfirmation->value,
            'reason' => 'Motivo fictício de desistência para teste.',
            'consequence_acknowledged' => true,
            'requested_at' => now(),
        ];
    }
}
