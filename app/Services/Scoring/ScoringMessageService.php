<?php

namespace App\Services\Scoring;

use App\Enums\ScoreCriterionResult;
use App\Models\ScoringCriterion;

class ScoringMessageService
{
    public function criterionMessage(ScoringCriterion $criterion, ScoreCriterionResult $result): string
    {
        return match ($result) {
            ScoreCriterionResult::Applied => $criterion->success_message ?: 'Critério aplicado à pontuação.',
            ScoreCriterionResult::RequiresManualReview => $criterion->review_message ?: 'Este critério requer avaliação manual autorizada.',
            ScoreCriterionResult::MissingData => 'Não existem dados suficientes para pontuar este critério.',
            ScoreCriterionResult::Manual => 'Pontuação manual registada.',
            ScoreCriterionResult::Failed => $criterion->failure_message ?: 'Critério avaliado sem atribuição de pontos.',
            ScoreCriterionResult::NotApplicable => 'Critério não aplicável aos dados disponíveis.',
        };
    }

    public function technicalMessage(
        ScoringCriterion $criterion,
        ScoreCriterionResult $result,
        mixed $actual,
        mixed $expected,
        ?float $points,
    ): string {
        return sprintf(
            'Critério %s avaliado: tipo=%s; operador=%s; resultado=%s; pontos=%s; valor=%s; esperado=%s.',
            $criterion->code,
            $criterion->calculation_type->value,
            $criterion->operator->value ?? 'none',
            $result->value,
            $points === null ? 'null' : number_format($points, 2, '.', ''),
            json_encode($this->safeValue($actual), JSON_UNESCAPED_UNICODE),
            json_encode($expected, JSON_UNESCAPED_UNICODE),
        );
    }

    public function safeValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item) => is_scalar($item) || $item === null ? $item : null)
                ->all();
        }

        return null;
    }
}
