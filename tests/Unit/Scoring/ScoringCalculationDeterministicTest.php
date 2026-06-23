<?php

namespace Tests\Unit\Scoring;

use App\Enums\ScoreCriterionResult;
use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Services\Scoring\ScoringCriterionEvaluator;
use Tests\TestCase;

class ScoringCalculationDeterministicTest extends TestCase
{
    public function test_weighted_fixed_points_are_calculated_with_explicit_expected_score(): void
    {
        $criterion = $this->criterion([
            'code' => 'household_size',
            'calculation_type' => ScoringCalculationType::FixedPoints->value,
            'operator' => ScoringOperator::IsTrue->value,
            'points' => 5,
            'weight' => 0.4,
            'max_points' => 10,
        ]);

        $result = app(ScoringCriterionEvaluator::class)->evaluate($criterion, [
            'values' => [
                'household_size' => [
                    'applicable' => true,
                    'value' => true,
                    'missing' => false,
                ],
            ],
        ]);

        $this->assertSame(ScoreCriterionResult::Applied, $result['result']);
        $this->assertSame(2.0, $result['points_awarded']);
    }

    public function test_proportional_score_respects_max_points_cap(): void
    {
        $criterion = $this->criterion([
            'code' => 'seniority_months',
            'calculation_type' => ScoringCalculationType::Proportional->value,
            'operator' => ScoringOperator::Exists->value,
            'weight' => 0.5,
            'max_points' => 8,
        ]);

        $result = app(ScoringCriterionEvaluator::class)->evaluate($criterion, [
            'values' => [
                'seniority_months' => [
                    'applicable' => true,
                    'value' => 20,
                    'missing' => false,
                ],
            ],
        ]);

        $this->assertSame(ScoreCriterionResult::Applied, $result['result']);
        $this->assertSame(8.0, $result['points_awarded']);
    }

    public function test_manual_scoring_criterion_requires_review_and_awards_no_automatic_points(): void
    {
        $criterion = $this->criterion([
            'code' => 'manual_social_assessment',
            'calculation_type' => ScoringCalculationType::Manual->value,
            'target' => 'manual',
            'requires_manual_review' => true,
            'points' => 0,
            'max_points' => 10,
        ]);

        $result = app(ScoringCriterionEvaluator::class)->evaluate($criterion, [
            'values' => [
                'manual_social_assessment' => [
                    'applicable' => true,
                    'value' => 5,
                    'missing' => false,
                ],
            ],
        ]);

        $this->assertSame(ScoreCriterionResult::RequiresManualReview, $result['result']);
        $this->assertSame(0.0, $result['points_awarded']);
        $this->assertTrue($result['requires_manual_review']);
    }

    private function criterion(array $attributes): ScoringCriterion
    {
        $criterion = new ScoringCriterion([
            'code' => 'criterion_s19',
            'name' => 'Critério de pontuação Sprint 19',
            'description' => 'Critério em memória para teste unitário.',
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
            'success_message' => 'Pontuação aplicada.',
            'failure_message' => 'Pontuação não aplicada.',
            'review_message' => 'Requer revisão.',
            ...$attributes,
        ]);

        $criterion->setRelation('rules', collect());

        return $criterion;
    }
}
