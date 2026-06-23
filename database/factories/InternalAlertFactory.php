<?php

namespace Database\Factories;

use App\Enums\InternalAlertSeverity;
use App\Enums\InternalAlertStatus;
use App\Enums\InternalAlertType;
use App\Models\InternalAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InternalAlert> */
class InternalAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alert_number' => 'ALT-TEST-'.fake()->unique()->numerify('######'),
            'type' => InternalAlertType::DeadlineApproaching,
            'severity' => InternalAlertSeverity::Warning,
            'status' => InternalAlertStatus::Open,
            'title' => 'Alerta operacional fictício',
            'message' => 'Alerta gerado para testes automatizados.',
            'assigned_to' => User::factory(),
            'due_at' => now()->addDays(3),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
