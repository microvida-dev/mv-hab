<?php

namespace Tests\Unit\Eligibility;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCriterion;
use App\Services\Eligibility\EligibilityCriteriaEvaluator;
use Tests\TestCase;

class EligibilityCalculationDeterministicTest extends TestCase
{
    public function test_income_inside_configured_bounds_passes(): void
    {
        $criterion = $this->criterion([
            'code' => 'income_below_maximum',
            'operator' => EligibilityOperator::Between->value,
            'minimum_value' => 10000,
            'maximum_value' => 20000,
        ]);

        $result = app(EligibilityCriteriaEvaluator::class)->evaluate($criterion, [
            'values' => [
                'income_below_maximum' => [
                    'applicable' => true,
                    'value' => 12000,
                    'missing' => false,
                ],
            ],
        ]);

        $this->assertSame(EligibilityCriterionResult::Passed, $result['result']);
        $this->assertSame(['value' => 12000], $result['actual_value']);
    }

    public function test_missing_required_document_returns_insufficient_data(): void
    {
        $criterion = $this->criterion([
            'code' => 'has_required_documents_submitted',
            'operator' => EligibilityOperator::AllRequiredDocumentsSubmitted->value,
        ]);

        $result = app(EligibilityCriteriaEvaluator::class)->evaluate($criterion, [
            'values' => [
                'has_required_documents_submitted' => [
                    'applicable' => true,
                    'value' => false,
                    'missing' => true,
                ],
            ],
        ]);

        $this->assertSame(EligibilityCriterionResult::InsufficientData, $result['result']);
    }

    public function test_manual_review_has_priority_over_boolean_result(): void
    {
        $criterion = $this->criterion([
            'code' => 'no_declared_property_impediment',
            'requires_manual_review' => true,
            'operator' => EligibilityOperator::IsTrue->value,
        ]);

        $result = app(EligibilityCriteriaEvaluator::class)->evaluate($criterion, [
            'values' => [
                'no_declared_property_impediment' => [
                    'applicable' => true,
                    'value' => true,
                    'missing' => false,
                ],
            ],
        ]);

        $this->assertSame(EligibilityCriterionResult::RequiresReview, $result['result']);
        $this->assertTrue($result['requires_manual_review']);
    }

    private function criterion(array $attributes): EligibilityCriterion
    {
        return new EligibilityCriterion([
            'code' => 'criterion_s19',
            'name' => 'Critério determinístico Sprint 19',
            'description' => 'Critério em memória para teste unitário.',
            'category' => EligibilityCriterionCategory::Other->value,
            'target' => 'calculated_value',
            'operator' => EligibilityOperator::IsTrue->value,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'success_message' => 'Condição cumprida.',
            'failure_message' => 'Condição não cumprida.',
            'review_message' => 'Requer revisão.',
            'sort_order' => 0,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
