<?php

namespace Database\Factories;

use App\Enums\TenantTransitionStatus;
use App\Models\Application;
use App\Models\TenantTransition;
use App\Models\User;
use App\Models\WinnerRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantTransition> */
class TenantTransitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'winner_registration_id' => WinnerRegistration::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'status' => TenantTransitionStatus::Pending->value,
            'preconditions' => [],
            'warnings' => [],
        ];
    }
}
