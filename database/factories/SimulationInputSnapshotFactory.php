<?php

namespace Database\Factories;

use App\Models\SimulationInputSnapshot;
use App\Models\SimulationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationInputSnapshot>
 */
class SimulationInputSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'simulation_session_id' => SimulationSession::factory(),
            'household_members_count' => 2,
            'adults_count' => 2,
            'dependents_count' => 0,
            'disabled_members_count' => 0,
            'monthly_income' => 1200,
            'annual_income' => 16800,
            'current_monthly_rent' => 450,
            'housing_status' => 'rented',
            'preferred_parishes' => [],
            'preferred_typologies' => ['T1'],
            'input_data' => [],
            'completeness_score' => 100,
            'contains_personal_data' => false,
        ];
    }
}
