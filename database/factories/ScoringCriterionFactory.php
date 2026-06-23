<?php

namespace Database\Factories;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoringCriterion>
 */
class ScoringCriterionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_rule_set_id' => ScoringRuleSet::factory(),
            'code' => fake()->unique()->slug(2),
            'name' => 'Critério fictício',
            'description' => 'Critério de teste.',
            'category' => 'other',
            'target' => 'calculated_value',
            'calculation_type' => ScoringCalculationType::FixedPoints->value,
            'operator' => ScoringOperator::Exists->value,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'points' => 10,
            'max_points' => 10,
            'weight' => 1,
            'requires_manual_review' => false,
            'is_exclusionary' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'code' => 'manual_assessment_'.fake()->unique()->numberBetween(1, 9999),
            'calculation_type' => ScoringCalculationType::Manual->value,
            'target' => 'manual',
            'requires_manual_review' => true,
            'points' => 0,
            'max_points' => 10,
        ]);
    }
}
