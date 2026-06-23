<?php

namespace App\Services\Scoring;

use App\Enums\ScoreCriterionResult;
use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ScoringCriterionEvaluator
{
    public function __construct(private readonly ScoringMessageService $messageService) {}

    /**
     * @param  array{values?: array<string, array{applicable?: bool, value?: mixed, missing?: bool}>}  $context
     * @return array<string, mixed>
     */
    public function evaluate(ScoringCriterion $criterion, array $context): array
    {
        $rawSource = $context['values'][$criterion->code] ?? [];
        $source = [
            'applicable' => (bool) ($rawSource['applicable'] ?? false),
            'value' => $rawSource['value'] ?? null,
            'missing' => (bool) ($rawSource['missing'] ?? true),
        ];

        $actual = $source['value'];
        $expected = $this->expected($criterion);
        $calculationType = $this->calculationType($criterion);

        if ($criterion->requires_manual_review || $calculationType === ScoringCalculationType::Manual) {
            return $this->result($criterion, ScoreCriterionResult::RequiresManualReview, $actual, $expected, 0);
        }

        if (! $source['applicable']) {
            return $this->result($criterion, ScoreCriterionResult::NotApplicable, $actual, $expected, 0);
        }

        if ($source['missing']) {
            return $this->result($criterion, ScoreCriterionResult::MissingData, $actual, $expected, 0);
        }

        $matchedRule = $this->matchingRule($criterion, $actual);

        if ($matchedRule) {
            return $this->result(
                $criterion,
                ScoreCriterionResult::Applied,
                $actual,
                $this->expectedArray($matchedRule->value ?? $expected),
                $this->weighted((float) $matchedRule->points, (float) $matchedRule->weight, $criterion),
                $matchedRule,
            );
        }

        $passed = $this->passes($this->operator($criterion->operator), $actual, $expected, $criterion);

        if ($calculationType === ScoringCalculationType::FixedPoints) {
            $passed = true;
        }

        if (! $passed) {
            return $this->result($criterion, ScoreCriterionResult::Failed, $actual, $expected, 0);
        }

        $points = match ($calculationType) {
            ScoringCalculationType::Proportional => $this->proportionalPoints($actual, $criterion),
            ScoringCalculationType::Weighted => $this->weighted((float) ($criterion->points ?? 0), (float) $criterion->weight, $criterion),
            default => $this->weighted((float) ($criterion->points ?? 0), (float) $criterion->weight, $criterion),
        };

        return $this->result($criterion, ScoreCriterionResult::Applied, $actual, $expected, $points);
    }

    private function matchingRule(ScoringCriterion $criterion, mixed $actual): ?ScoringRule
    {
        $criterion->loadMissing('rules');

        /** @var EloquentCollection<int, ScoringRule> $rules */
        $rules = $criterion->rules;

        return $rules
            ->where('is_active', true)
            ->first(fn (ScoringRule $rule) => $this->passes(
                $this->operator($rule->operator ?? $criterion->operator),
                $actual,
                $this->expectedArray($rule->value ?? []),
                $criterion,
                $rule,
            ));
    }

    /**
     * @param  array<string, mixed>  $expected
     */
    private function passes(
        ?ScoringOperator $operator,
        mixed $actual,
        array $expected,
        ScoringCriterion $criterion,
        ?ScoringRule $rule = null,
    ): bool {
        $operator ??= ScoringOperator::Exists;
        $value = $expected['value'] ?? null;
        $ruleMinimum = $rule instanceof ScoringRule ? $rule->minimum_value : null;
        $ruleMaximum = $rule instanceof ScoringRule ? $rule->maximum_value : null;
        $minimum = $expected['minimum'] ?? $ruleMinimum ?? $criterion->minimum_value;
        $maximum = $expected['maximum'] ?? $ruleMaximum ?? $criterion->maximum_value;

        return match ($operator) {
            ScoringOperator::Equals => $actual == $value,
            ScoringOperator::NotEquals => $actual != $value,
            ScoringOperator::GreaterThan => $this->compareNumeric($actual, $value ?? $minimum, '>'),
            ScoringOperator::GreaterThanOrEqual => $this->compareNumeric($actual, $value ?? $minimum, '>='),
            ScoringOperator::LessThan => $this->compareNumeric($actual, $value ?? $maximum, '<'),
            ScoringOperator::LessThanOrEqual => $this->compareNumeric($actual, $value ?? $maximum, '<='),
            ScoringOperator::Between => is_numeric($actual)
                && $minimum !== null
                && $maximum !== null
                && $actual >= $minimum
                && $actual <= $maximum,
            ScoringOperator::IsTrue => $actual === true || $actual === 1 || $actual === '1',
            ScoringOperator::IsFalse => $actual === false || $actual === 0 || $actual === '0',
            ScoringOperator::Exists => filled($actual),
            ScoringOperator::NotExists => blank($actual),
            ScoringOperator::In => in_array($actual, $expected['values'] ?? [], true),
            ScoringOperator::NotIn => ! in_array($actual, $expected['values'] ?? [], true),
            ScoringOperator::Custom => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function expected(ScoringCriterion $criterion): array
    {
        $expected = $this->expectedArray($criterion->expected_value ?? []);

        if ($criterion->minimum_value !== null) {
            $expected['minimum'] = (float) $criterion->minimum_value;
        }

        if ($criterion->maximum_value !== null) {
            $expected['maximum'] = (float) $criterion->maximum_value;
        }

        return $expected;
    }

    /**
     * @return array<string, mixed>
     */
    private function expectedArray(mixed $value): array
    {
        return is_array($value) ? $value : ['value' => $value];
    }

    private function calculationType(ScoringCriterion $criterion): ScoringCalculationType
    {
        $calculationType = $criterion->getAttribute('calculation_type');

        return $calculationType instanceof ScoringCalculationType
            ? $calculationType
            : ScoringCalculationType::from((string) $calculationType);
    }

    private function operator(mixed $operator): ?ScoringOperator
    {
        if ($operator === null || $operator instanceof ScoringOperator) {
            return $operator;
        }

        return ScoringOperator::from((string) $operator);
    }

    private function compareNumeric(mixed $actual, mixed $threshold, string $operator): bool
    {
        if (! is_numeric($actual) || ! is_numeric($threshold)) {
            return false;
        }

        return match ($operator) {
            '>' => (float) $actual > (float) $threshold,
            '>=' => (float) $actual >= (float) $threshold,
            '<' => (float) $actual < (float) $threshold,
            '<=' => (float) $actual <= (float) $threshold,
            default => false,
        };
    }

    private function proportionalPoints(mixed $actual, ScoringCriterion $criterion): float
    {
        if (! is_numeric($actual)) {
            return 0;
        }

        $points = (float) $actual * (float) $criterion->weight;

        return $criterion->max_points === null
            ? round($points, 2)
            : min(round($points, 2), (float) $criterion->max_points);
    }

    private function weighted(float $points, float $weight, ScoringCriterion $criterion): float
    {
        $weighted = round($points * max($weight, 0), 2);

        return $criterion->max_points === null
            ? $weighted
            : min($weighted, (float) $criterion->max_points);
    }

    /**
     * @param  array<string, mixed>  $expected
     * @return array<string, mixed>
     */
    private function result(
        ScoringCriterion $criterion,
        ScoreCriterionResult $result,
        mixed $actual,
        array $expected,
        float $points,
        ?ScoringRule $rule = null,
    ): array {
        return [
            'result' => $result,
            'points_awarded' => $points,
            'raw_value' => ['value' => $this->messageService->safeValue($actual)],
            'normalized_value' => ['expected' => $expected],
            'message' => $this->messageService->criterionMessage($criterion, $result),
            'technical_message' => $this->messageService->technicalMessage($criterion, $result, $actual, $expected, $points),
            'requires_manual_review' => $result === ScoreCriterionResult::RequiresManualReview,
            'scoring_rule_id' => $rule?->id,
        ];
    }
}
