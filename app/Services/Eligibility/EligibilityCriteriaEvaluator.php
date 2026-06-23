<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCriterion;

/**
 * @phpstan-type EligibilityScalar bool|float|int|string|null
 * @phpstan-type EligibilitySource array{applicable: bool, value: EligibilityScalar, missing: bool}
 * @phpstan-type ExpectedValue array{value?: EligibilityScalar, values?: list<EligibilityScalar>, minimum?: float, maximum?: float}
 * @phpstan-type EvaluationResult array{result: EligibilityCriterionResult, actual_value: array{value: EligibilityScalar}, expected_value: ExpectedValue, message: string, technical_message: string, requires_manual_review: bool}
 */
class EligibilityCriteriaEvaluator
{
    public function __construct(private readonly EligibilityMessageService $messageService) {}

    /**
     * @param  array{values: array<string, EligibilitySource>}  $context
     * @return EvaluationResult
     */
    public function evaluate(EligibilityCriterion $criterion, array $context): array
    {
        $source = $context['values'][$criterion->code] ?? [
            'applicable' => false,
            'value' => null,
            'missing' => false,
        ];
        $actual = $source['value'];
        $expected = $this->expected($criterion);

        $result = match (true) {
            ! $source['applicable'] => EligibilityCriterionResult::NotApplicable,
            $criterion->requires_manual_review => EligibilityCriterionResult::RequiresReview,
            $source['missing'] => EligibilityCriterionResult::InsufficientData,
            default => $this->passes($criterion->operator, $actual, $expected, $criterion)
                ? EligibilityCriterionResult::Passed
                : EligibilityCriterionResult::Failed,
        };

        return [
            'result' => $result,
            'actual_value' => ['value' => $this->safeValue($actual)],
            'expected_value' => $expected,
            'message' => $this->messageService->criterionMessage($criterion, $result),
            'technical_message' => $this->messageService->technicalMessage(
                $criterion,
                $result,
                $actual,
                $expected,
            ),
            'requires_manual_review' => $result === EligibilityCriterionResult::RequiresReview,
        ];
    }

    /**
     * @param  ExpectedValue  $expected
     */
    private function passes(
        EligibilityOperator $operator,
        mixed $actual,
        array $expected,
        EligibilityCriterion $criterion,
    ): bool {
        $value = $expected['value'] ?? null;

        return match ($operator) {
            EligibilityOperator::Equals => $actual == $value,
            EligibilityOperator::NotEquals => $actual != $value,
            EligibilityOperator::GreaterThan => is_numeric($actual) && $actual > ($value ?? $criterion->minimum_value),
            EligibilityOperator::GreaterThanOrEqual => is_numeric($actual) && $actual >= ($value ?? $criterion->minimum_value),
            EligibilityOperator::LessThan => is_numeric($actual) && $actual < ($value ?? $criterion->maximum_value),
            EligibilityOperator::LessThanOrEqual => is_numeric($actual) && $actual <= ($value ?? $criterion->maximum_value),
            EligibilityOperator::Between => is_numeric($actual)
                && $actual >= ($expected['minimum'] ?? $criterion->minimum_value)
                && $actual <= ($expected['maximum'] ?? $criterion->maximum_value),
            EligibilityOperator::IsTrue,
            EligibilityOperator::AllRequiredDocumentsSubmitted,
            EligibilityOperator::AllRequiredDocumentsValidated => $actual === true,
            EligibilityOperator::IsFalse => $actual === false,
            EligibilityOperator::Exists => filled($actual),
            EligibilityOperator::NotExists => blank($actual),
            EligibilityOperator::In => in_array($actual, $expected['values'] ?? [], true),
            EligibilityOperator::NotIn => ! in_array($actual, $expected['values'] ?? [], true),
            EligibilityOperator::Custom => false,
        };
    }

    /**
     * @return ExpectedValue
     */
    private function expected(EligibilityCriterion $criterion): array
    {
        /** @var ExpectedValue $expected */
        $expected = is_array($criterion->expected_value) ? $criterion->expected_value : [];

        if ($criterion->minimum_value !== null) {
            $expected['minimum'] = (float) $criterion->minimum_value;
        }

        if ($criterion->maximum_value !== null) {
            $expected['maximum'] = (float) $criterion->maximum_value;
        }

        return $expected;
    }

    private function safeValue(mixed $value): bool|float|int|string|null
    {
        return is_scalar($value) || $value === null ? $value : null;
    }
}
