<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\Contest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesRequiredDocumentConfiguration
{
    /**
     * @return array<string, mixed>
     */
    protected function requiredDocumentRules(): array
    {
        return [
            'document_type_id' => [
                'required',
                'integer',
                'exists:document_types,id',
            ],
            'program_id' => [
                'nullable',
                'integer',
                'exists:programs,id',
            ],
            'contest_id' => [
                'nullable',
                'integer',
                'exists:contests,id',
            ],
            'required_for' => [
                'required',
                Rule::enum(DocumentAppliesTo::class),
            ],
            'condition_key' => [
                'required',
                'string',
                'max:150',
            ],
            'condition_operator' => [
                'required',
                Rule::enum(RequiredDocumentConditionOperator::class),
            ],
            'condition_value' => [
                'nullable',
                'string',
                'max:255',
            ],
            'required_submissions' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],
            'reference_period_unit' => [
                'nullable',
                Rule::enum(DocumentReferencePeriodUnit::class),
            ],
            'requires_distinct_reference_periods' => [
                'sometimes',
                'boolean',
            ],
            'reference_period_recency' => [
                'nullable',
                'integer',
                'min:1',
                'max:24',
            ],
            'is_required' => [
                'sometimes',
                'boolean',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();

            $this->validatePeriodConfiguration(
                $validator,
                $data,
            );

            $this->validateContestProgramScope(
                $validator,
                $data,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validatePeriodConfiguration(
        Validator $validator,
        array $data,
    ): void {
        $referencePeriodUnit = $data['reference_period_unit']
            ?? null;

        $requiresDistinctPeriods = filter_var(
            $data['requires_distinct_reference_periods'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        $requiredSubmissions = (int) (
            $data['required_submissions'] ?? 1
        );

        $referencePeriodRecency = $data['reference_period_recency']
            ?? null;

        if ($requiresDistinctPeriods
            && blank($referencePeriodUnit)) {
            $validator->errors()->add(
                'reference_period_unit',
                'Selecione uma periodicidade para exigir períodos distintos.',
            );
        }

        if (filled($referencePeriodRecency)
            && blank($referencePeriodUnit)) {
            $validator->errors()->add(
                'reference_period_unit',
                'Selecione uma periodicidade para configurar a antiguidade máxima.',
            );
        }

        if ($requiresDistinctPeriods
            && $requiredSubmissions < 2) {
            $validator->errors()->add(
                'requires_distinct_reference_periods',
                'A distinção de períodos exige pelo menos duas submissões.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateContestProgramScope(
        Validator $validator,
        array $data,
    ): void {
        $contestId = (int) ($data['contest_id'] ?? 0);
        $programId = (int) ($data['program_id'] ?? 0);

        if ($contestId < 1 || $programId < 1) {
            return;
        }

        $contestBelongsToProgram = Contest::query()
            ->whereKey($contestId)
            ->where('program_id', $programId)
            ->exists();

        if (! $contestBelongsToProgram) {
            $validator->errors()->add(
                'contest_id',
                'O concurso selecionado não pertence ao programa indicado.',
            );
        }
    }
}
