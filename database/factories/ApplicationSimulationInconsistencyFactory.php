<?php

namespace Database\Factories;

use App\Enums\InconsistencySeverity;
use App\Enums\InconsistencyType;
use App\Models\Application;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationSimulationInconsistency>
 */
class ApplicationSimulationInconsistencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'type' => InconsistencyType::IncomeChanged->value,
            'severity' => InconsistencySeverity::Warning->value,
            'field_name' => 'monthly_income',
            'simulation_value' => ['value' => 900],
            'application_value' => ['value' => 1000],
            'message' => 'Foram detetadas diferenças entre a simulação e a candidatura.',
            'recommendation' => 'Rever os dados declarados antes da submissão.',
            'is_resolved' => false,
        ];
    }
}
