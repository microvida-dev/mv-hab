<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityResult;
use App\Models\EligibilityCriterion;

class EligibilityMessageService
{
    public function candidateSummary(EligibilityResult $result): string
    {
        return match ($result) {
            EligibilityResult::Eligible => 'Com base nos dados atualmente declarados, reúne as condições mínimas indicadas para este programa ou concurso. A confirmação final dependerá da análise dos serviços municipais.',
            EligibilityResult::Ineligible => 'Com base nos dados atualmente declarados, existem condições mínimas que não se encontram cumpridas. Consulte os pontos assinalados e confirme se os seus dados estão corretos.',
            EligibilityResult::InsufficientData => 'Não existem ainda dados suficientes para avaliar a elegibilidade. Complete o seu registo, agregado, rendimentos, situação habitacional e documentos.',
            EligibilityResult::RequiresReview => 'Alguns elementos exigem análise pelos serviços municipais. O resultado apresentado é indicativo e poderá ser confirmado posteriormente.',
            EligibilityResult::NotApplicable => 'Não foi possível aplicar regras de elegibilidade ao contexto selecionado.',
        };
    }

    public function criterionMessage(EligibilityCriterion $criterion, EligibilityCriterionResult $result): string
    {
        return match ($result) {
            EligibilityCriterionResult::Passed => $criterion->success_message ?: 'Condição cumprida com base nos dados declarados.',
            EligibilityCriterionResult::Failed => $criterion->failure_message ?: 'Esta condição mínima não se encontra cumprida.',
            EligibilityCriterionResult::RequiresReview => $criterion->review_message ?: 'Este elemento requer análise pelos serviços municipais.',
            EligibilityCriterionResult::InsufficientData => 'Não existem dados suficientes para avaliar esta condição.',
            EligibilityCriterionResult::NotApplicable => 'Este critério não se aplica ao contexto selecionado.',
        };
    }

    public function technicalMessage(
        EligibilityCriterion $criterion,
        EligibilityCriterionResult $result,
        mixed $actual,
        mixed $expected,
    ): string {
        return sprintf(
            'Critério %s avaliado com operador %s: resultado=%s; valor_atual=%s; valor_esperado=%s.',
            $criterion->code,
            $criterion->operator->value,
            $result->value,
            json_encode($actual, JSON_UNESCAPED_UNICODE),
            json_encode($expected, JSON_UNESCAPED_UNICODE),
        );
    }
}
