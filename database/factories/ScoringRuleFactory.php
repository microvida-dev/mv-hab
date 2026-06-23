<?php

namespace Database\Factories;

use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringRule>
 */
class ScoringRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_criterion_id' => ScoringCriterion::factory(),
            'label' => 'Regra fictícia',
            'description' => 'Regra de teste.',
            'operator' => ScoringOperator::Exists->value,
            'value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'points' => 5,
            'weight' => 1,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
