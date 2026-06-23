<?php

namespace Database\Factories;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityCriterion>
 */
class EligibilityCriterionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'eligibility_rule_set_id' => EligibilityRuleSet::factory(),
            'code' => 'criterion_'.fake()->unique()->slug(2),
            'name' => 'Critério '.fake()->word().' '.fake()->unique()->word(),
            'description' => 'Critério fictício para testes.',
            'category' => EligibilityCriterionCategory::Other->value,
            'target' => 'calculated_value',
            'operator' => EligibilityOperator::IsTrue->value,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'failure_message' => 'Condição não cumprida.',
            'success_message' => 'Condição cumprida.',
            'review_message' => 'Condição sujeita a análise.',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
