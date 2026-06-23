<?php

namespace Database\Factories;

use App\Enums\ApplicationScoreStatus;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationScore>
 */
class ApplicationScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_run_id' => ScoringRun::factory(),
            'application_id' => Application::factory()->submitted(),
            'scoring_rule_set_id' => ScoringRuleSet::factory(),
            'program_id' => null,
            'contest_id' => null,
            'user_id' => null,
            'status' => ApplicationScoreStatus::Calculated->value,
            'total_score' => 10,
            'automatic_score' => 10,
            'manual_score' => 0,
            'tie_breaker_values' => null,
            'rank_position' => null,
            'is_tied' => false,
            'requires_manual_review' => false,
            'excluded_from_ranking' => false,
            'calculated_at' => now(),
        ];
    }
}
