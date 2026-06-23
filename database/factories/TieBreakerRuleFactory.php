<?php

namespace Database\Factories;

use App\Enums\TieBreakerDirection;
use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TieBreakerRule>
 */
class TieBreakerRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_rule_set_id' => ScoringRuleSet::factory(),
            'code' => 'submitted_at',
            'name' => 'Data de submissão',
            'description' => 'Desempate fictício por antiguidade de submissão.',
            'target' => 'submitted_at',
            'direction' => TieBreakerDirection::Asc->value,
            'priority_order' => 10,
            'is_active' => true,
        ];
    }
}
