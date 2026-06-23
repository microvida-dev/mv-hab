<?php

namespace Database\Factories;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCheck;
use App\Models\EligibilityCheckResult;
use App\Models\EligibilityCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EligibilityCheckResult>
 */
class EligibilityCheckResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'eligibility_check_id' => EligibilityCheck::factory(),
            'eligibility_criterion_id' => EligibilityCriterion::factory(),
            'code' => 'criterion_test',
            'name' => 'Critério de teste',
            'category' => EligibilityCriterionCategory::Other->value,
            'result' => EligibilityCriterionResult::Passed->value,
            'actual_value' => ['value' => true],
            'expected_value' => [],
            'operator' => EligibilityOperator::IsTrue->value,
            'message' => 'Condição cumprida.',
            'technical_message' => 'Mensagem técnica fictícia.',
            'requires_manual_review' => false,
        ];
    }
}
