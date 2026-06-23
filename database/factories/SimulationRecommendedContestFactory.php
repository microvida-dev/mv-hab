<?php

namespace Database\Factories;

use App\Enums\SimulationContestMatchStatus;
use App\Models\Contest;
use App\Models\SimulationRecommendedContest;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SimulationRecommendedContest>
 */
class SimulationRecommendedContestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'simulation_session_id' => SimulationSession::factory(),
            'simulation_result_id' => SimulationResult::factory(),
            'contest_id' => Contest::factory(),
            'match_status' => SimulationContestMatchStatus::Possible->value,
            'match_score' => 70,
            'public_status' => 'open',
            'recommended_typologies' => ['T1'],
            'reasons' => ['Compatível para teste.'],
            'warnings' => [],
        ];
    }
}
