<?php

namespace Database\Factories;

use App\Enums\RentEstimateStatus;
use App\Enums\SimulationResultStatus;
use App\Enums\TypologyRecommendationStatus;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationResult>
 */
class SimulationResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'simulation_session_id' => SimulationSession::factory(),
            'result_status' => SimulationResultStatus::LikelyEligible->value,
            'eligibility_summary' => 'Resultado indicativo.',
            'eligibility_score' => 90,
            'eligibility_payload' => ['indicative' => true],
            'typology_status' => TypologyRecommendationStatus::Recommended->value,
            'recommended_typology' => 'T1',
            'recommended_bedrooms' => 1,
            'typology_payload' => [],
            'rent_status' => RentEstimateStatus::Estimated->value,
            'estimated_rent_min' => 250,
            'estimated_rent_max' => 420,
            'estimated_effort_rate' => 35,
            'rent_payload' => [],
            'recommendations_payload' => [],
        ];
    }
}
