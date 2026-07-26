<?php

namespace App\Http\Requests;

use App\Enums\ContractTemplateStatus;
use App\Models\ContractTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', ContractTemplate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $municipalityId = $this->user()?->municipality_id;

        return [
            'program_id' => [
                'nullable',
                'required_without:contest_id',
                Rule::exists('programs', 'id')->when(
                    $municipalityId !== null,
                    fn ($rule) => $rule->where('municipality_id', $municipalityId),
                ),
            ],
            'contest_id' => [
                'nullable',
                'required_without:program_id',
                Rule::exists('contests', 'id')->when(
                    $municipalityId !== null,
                    fn ($rule) => $rule->where(
                        fn ($contests) => $contests->whereIn(
                            'program_id',
                            fn ($programs) => $programs
                                ->select('id')
                                ->from('programs')
                                ->where('municipality_id', $municipalityId),
                        ),
                    ),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in([ContractTemplateStatus::Draft->value])],
            'version_number' => ['nullable', 'integer', 'min:1'],
            'template_body' => ['required', 'string', 'min:50'],
            'header_html' => ['nullable', 'string', 'max:10000'],
            'footer_html' => ['nullable', 'string', 'max:10000'],
            'clause_ids' => ['nullable', 'array'],
            'clause_ids.*' => [
                'integer',
                Rule::exists('contract_clauses', 'id')->when(
                    $municipalityId !== null,
                    fn ($rule) => $rule->where(function ($clauses) use ($municipalityId): void {
                        $clauses
                            ->whereIn(
                                'program_id',
                                fn ($programs) => $programs
                                    ->select('id')
                                    ->from('programs')
                                    ->where('municipality_id', $municipalityId),
                            )
                            ->orWhereIn(
                                'contest_id',
                                fn ($contests) => $contests
                                    ->select('contests.id')
                                    ->from('contests')
                                    ->join('programs', 'programs.id', '=', 'contests.program_id')
                                    ->where('programs.municipality_id', $municipalityId),
                            );
                    }),
                ),
            ],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
