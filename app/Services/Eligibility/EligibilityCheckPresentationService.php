<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use App\Models\EligibilityCheck;
use App\Models\EligibilityCheckResult;
use Illuminate\Support\Collection;

class EligibilityCheckPresentationService
{
    /**
     * @return array{
     *     missingData: list<array{label: string, guidance: string}>,
     *     results: list<array{
     *         name: string,
     *         category: string,
     *         result: string,
     *         tone: string,
     *         message: string,
     *         condition: string,
     *         actual: string,
     *         requiresAttention: bool
     *     }>,
     *     attentionResults: list<array{
     *         name: string,
     *         category: string,
     *         result: string,
     *         tone: string,
     *         message: string,
     *         condition: string,
     *         actual: string,
     *         requiresAttention: bool
     *     }>
     * }
     */
    public function present(EligibilityCheck $check): array
    {
        $resultsByCode = new Collection;
        $results = [];
        $attentionResults = [];

        foreach ($check->results as $result) {
            if ($result->code !== '') {
                $resultsByCode->put($result->code, $result);
            }

            $presentedResult = $this->presentResult($result);
            $results[] = $presentedResult;

            if ($presentedResult['requiresAttention']) {
                $attentionResults[] = $presentedResult;
            }
        }

        return [
            'missingData' => $this->presentMissingData($this->missingData($check), $resultsByCode),
            'results' => $results,
            'attentionResults' => $attentionResults,
        ];
    }

    /**
     * @return list<string>
     */
    private function missingData(EligibilityCheck $check): array
    {
        $value = $check->getAttribute('missing_data');

        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  array<int, string>  $missingData
     * @param  Collection<string, EligibilityCheckResult>  $resultsByCode
     * @return list<array{label: string, guidance: string}>
     */
    private function presentMissingData(array $missingData, Collection $resultsByCode): array
    {
        $items = [];

        foreach ($missingData as $code) {
            $result = $resultsByCode->get($code);

            if ($result instanceof EligibilityCheckResult) {
                $items[] = [
                    'label' => $result->name,
                    'guidance' => $this->conditionForResult($result),
                ];

                continue;
            }

            $items[] = [
                'label' => $this->labelForCode($code),
                'guidance' => $this->guidanceForCode($code),
            ];
        }

        return $items;
    }

    /**
     * @return array{
     *     name: string,
     *     category: string,
     *     result: string,
     *     tone: string,
     *     message: string,
     *     condition: string,
     *     actual: string,
     *     requiresAttention: bool
     * }
     */
    private function presentResult(EligibilityCheckResult $result): array
    {
        $criterionResult = $this->criterionResult($result);

        return [
            'name' => $result->name,
            'category' => $this->categoryLabel($result),
            'result' => $criterionResult->label(),
            'tone' => $this->tone($criterionResult),
            'message' => $result->message ?: $this->conditionForResult($result),
            'condition' => $this->conditionForResult($result),
            'actual' => $this->actualSummary($result, $criterionResult),
            'requiresAttention' => in_array($criterionResult, [
                EligibilityCriterionResult::Failed,
                EligibilityCriterionResult::InsufficientData,
                EligibilityCriterionResult::RequiresReview,
            ], true),
        ];
    }

    private function criterionResult(EligibilityCheckResult $result): EligibilityCriterionResult
    {
        $value = $result->getAttribute('result');

        return $value instanceof EligibilityCriterionResult
            ? $value
            : EligibilityCriterionResult::from((string) $value);
    }

    private function categoryLabel(EligibilityCheckResult $result): string
    {
        $category = $result->getAttribute('category');

        return $category instanceof EligibilityCriterionCategory
            ? $category->label()
            : 'Elegibilidade';
    }

    private function operator(EligibilityCheckResult $result): EligibilityOperator
    {
        $operator = $result->getAttribute('operator');

        return $operator instanceof EligibilityOperator
            ? $operator
            : EligibilityOperator::from((string) $operator);
    }

    private function conditionForResult(EligibilityCheckResult $result): string
    {
        $expected = $this->arrayAttribute($result, 'expected_value');

        return match ($result->code) {
            'all_non_dependent_adults_meet_rmmg' => 'Cada adulto não dependente do agregado deve comprovar rendimento mensal mínimo de '.$this->formatCurrency($expected['monthly_minimum'] ?? null, $expected['currency'] ?? 'EUR').' (RMMG '.$this->formatYear($expected['reference_year'] ?? null).').',
            'typology_is_adequate' => 'A tipologia ou fogo escolhido deve ser compatível com a dimensão e composição do agregado.',
            'rent_effort_within_35_percent' => 'A renda estimada deve manter a taxa de esforço até '.$this->formatPercent($expected['maximum_percentage'] ?? 35).' do rendimento mensal do agregado.',
            'has_income_information' => 'Devem existir rendimentos declarados ou indicação expressa de ausência de rendimentos para todos os membros aplicáveis.',
            'has_required_documents_submitted' => 'Todos os documentos obrigatórios definidos no programa ou concurso devem estar submetidos.',
            'has_required_documents_validated' => 'Todos os documentos obrigatórios definidos no programa ou concurso devem estar validados pelos serviços municipais.',
            'registration_is_registered' => 'O registo de adesão deve estar finalizado antes da candidatura.',
            'candidate_is_adult' => 'O titular da candidatura deve ter pelo menos 18 anos.',
            'contest_is_open' => 'O concurso deve estar aberto e dentro do prazo de candidatura.',
            'has_household' => 'O agregado habitacional deve estar criado e associado ao registo de adesão.',
            'has_applicant_member' => 'O agregado deve ter um titular identificado.',
            'has_current_housing_situation' => 'A situação habitacional atual deve estar preenchida.',
            'no_declared_property_impediment' => 'Não devem existir impedimentos patrimoniais declarados que excluam o acesso ao concurso.',
            default => $this->genericCondition($result, $expected),
        };
    }

    /**
     * @param  array<string, mixed>  $expected
     */
    private function genericCondition(EligibilityCheckResult $result, array $expected): string
    {
        if (isset($expected['minimum'])) {
            return 'Valor mínimo exigido: '.$this->formatValue($expected['minimum']).'.';
        }

        if (isset($expected['monthly_minimum'])) {
            return 'Valor mensal mínimo exigido: '.$this->formatCurrency($expected['monthly_minimum'], $expected['currency'] ?? 'EUR').'.';
        }

        if (isset($expected['maximum'])) {
            return 'Valor máximo permitido: '.$this->formatValue($expected['maximum']).'.';
        }

        if (isset($expected['maximum_percentage'])) {
            return 'Percentagem máxima permitida: '.$this->formatPercent($expected['maximum_percentage']).'.';
        }

        return match ($this->operator($result)) {
            EligibilityOperator::IsTrue => 'Esta condição deve estar comprovada no processo.',
            EligibilityOperator::IsFalse => 'Esta condição não pode estar presente no processo.',
            EligibilityOperator::Exists => 'Este dado deve existir no processo.',
            EligibilityOperator::NotExists => 'Este dado não pode existir no processo.',
            EligibilityOperator::AllRequiredDocumentsSubmitted => 'Todos os documentos obrigatórios devem estar submetidos.',
            EligibilityOperator::AllRequiredDocumentsValidated => 'Todos os documentos obrigatórios devem estar validados.',
            default => 'Consulte a regra configurada no programa ou concurso e confirme os dados declarados.',
        };
    }

    private function actualSummary(
        EligibilityCheckResult $result,
        EligibilityCriterionResult $criterionResult,
    ): string {
        if ($criterionResult === EligibilityCriterionResult::InsufficientData) {
            return 'Não existem dados suficientes para avaliar esta condição.';
        }

        if ($criterionResult === EligibilityCriterionResult::RequiresReview) {
            return 'A condição exige confirmação pelos serviços municipais.';
        }

        return match ($result->code) {
            'all_non_dependent_adults_meet_rmmg' => $criterionResult === EligibilityCriterionResult::Passed
                ? 'Todos os adultos não dependentes cumprem o mínimo exigido.'
                : 'Pelo menos um adulto não dependente está abaixo do mínimo exigido ou sem comprovativo suficiente.',
            'typology_is_adequate' => $criterionResult === EligibilityCriterionResult::Passed
                ? 'A tipologia encontra-se compatível.'
                : 'A compatibilidade tipológica ainda não está confirmada.',
            'rent_effort_within_35_percent' => $criterionResult === EligibilityCriterionResult::Passed
                ? 'A taxa de esforço encontra-se dentro do limite.'
                : 'A taxa de esforço ainda não está calculada ou excede o limite configurado.',
            default => $this->formatActualValue($this->arrayAttribute($result, 'actual_value'), $criterionResult),
        };
    }

    /**
     * @param  array<string, mixed>  $actual
     */
    private function formatActualValue(array $actual, EligibilityCriterionResult $criterionResult): string
    {
        if (array_key_exists('value', $actual)) {
            return $this->formatValue($actual['value']);
        }

        if ($actual !== []) {
            return $criterionResult === EligibilityCriterionResult::Passed
                ? 'Condição comprovada.'
                : 'Condição não comprovada com os dados atuais.';
        }

        return match ($criterionResult) {
            EligibilityCriterionResult::Passed => 'Condição comprovada.',
            EligibilityCriterionResult::Failed => 'Condição não comprovada com os dados atuais.',
            EligibilityCriterionResult::NotApplicable => 'Condição não aplicável.',
            EligibilityCriterionResult::InsufficientData => 'Dados insuficientes.',
            EligibilityCriterionResult::RequiresReview => 'Requer análise municipal.',
        };
    }

    private function tone(EligibilityCriterionResult $result): string
    {
        return match ($result) {
            EligibilityCriterionResult::Passed => 'success',
            EligibilityCriterionResult::Failed => 'danger',
            EligibilityCriterionResult::RequiresReview => 'warning',
            EligibilityCriterionResult::InsufficientData => 'info',
            EligibilityCriterionResult::NotApplicable => 'neutral',
        };
    }

    private function labelForCode(string $code): string
    {
        return match ($code) {
            'all_non_dependent_adults_meet_rmmg' => 'Rendimento mínimo dos adultos não dependentes',
            'typology_is_adequate' => 'Composição adequada às tipologias escolhidas',
            'rent_effort_within_35_percent' => 'Taxa de esforço máxima de 35%',
            'has_income_information' => 'Rendimentos do agregado',
            'has_required_documents_submitted' => 'Documentos obrigatórios submetidos',
            'has_required_documents_validated' => 'Documentos obrigatórios validados',
            'registration_is_registered' => 'Registo de adesão finalizado',
            'candidate_is_adult' => 'Idade mínima de 18 anos',
            'contest_is_open' => 'Concurso aberto',
            'has_household' => 'Agregado habitacional',
            'has_applicant_member' => 'Titular do agregado',
            'has_current_housing_situation' => 'Situação habitacional atual',
            'no_declared_property_impediment' => 'Impedimentos patrimoniais',
            default => 'Condição de elegibilidade',
        };
    }

    private function guidanceForCode(string $code): string
    {
        return match ($code) {
            'all_non_dependent_adults_meet_rmmg' => 'Confirme os rendimentos declarados e os comprovativos dos adultos não dependentes.',
            'typology_is_adequate' => 'Confirme a composição do agregado e a tipologia/fogo selecionado no concurso.',
            'rent_effort_within_35_percent' => 'Confirme os rendimentos mensais e a renda estimada para o fogo escolhido.',
            'has_income_information' => 'Complete os rendimentos do agregado ou indique expressamente ausência de rendimentos.',
            'has_required_documents_submitted' => 'Submeta todos os documentos obrigatórios do programa ou concurso.',
            'has_required_documents_validated' => 'Aguarde ou conclua a validação municipal dos documentos obrigatórios.',
            default => 'Complete os dados em falta no registo, candidatura ou documentação associada.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAttribute(EligibilityCheckResult $result, string $attribute): array
    {
        $value = $result->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }

    private function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, ',', ' ');
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return 'Não indicado';
    }

    private function formatCurrency(mixed $value, mixed $currency): string
    {
        if (! is_numeric($value)) {
            return 'valor não configurado';
        }

        $suffix = is_string($currency) && $currency !== '' ? $currency : 'EUR';
        $symbol = $suffix === 'EUR' ? '€' : $suffix;

        return number_format((float) $value, 2, ',', ' ').' '.$symbol;
    }

    private function formatPercent(mixed $value): string
    {
        if (! is_numeric($value)) {
            return 'percentagem não configurada';
        }

        $number = (float) $value;
        $decimals = fmod($number, 1.0) === 0.0 ? 0 : 1;

        return number_format($number, $decimals, ',', ' ').'%';
    }

    private function formatYear(mixed $value): string
    {
        return is_numeric($value) ? (string) (int) $value : 'ano de referência configurado';
    }
}
