<?php

namespace Database\Factories;

use App\Enums\ImpedimentSeverity;
use App\Enums\ImpedimentType;
use App\Models\SimulationImpediment;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationImpediment>
 */
class SimulationImpedimentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'simulation_session_id' => SimulationSession::factory(),
            'simulation_result_id' => SimulationResult::factory(),
            'type' => ImpedimentType::ManualReviewRequired->value,
            'severity' => ImpedimentSeverity::Warning->value,
            'code' => 'factory_warning',
            'title' => 'Aviso',
            'message' => 'Aviso de teste.',
            'is_blocking' => false,
        ];
    }
}
