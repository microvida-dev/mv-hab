<?php

namespace Database\Factories;

use App\Enums\ScoringRunStatus;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringRun>
 */
class ScoringRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_rule_set_id' => ScoringRuleSet::factory(),
            'program_id' => Program::factory(),
            'contest_id' => null,
            'status' => ScoringRunStatus::Draft->value,
            'started_by' => User::factory(),
            'started_at' => null,
            'notes' => 'Execução fictícia para teste.',
        ];
    }
}
