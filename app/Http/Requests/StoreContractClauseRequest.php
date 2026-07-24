<?php

namespace App\Http\Requests;

use App\Enums\ContractClauseStatus;
use App\Models\ContractClause;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractClauseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', ContractClause::class) ?? false;
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
            'code' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:20000'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in([ContractClauseStatus::Draft->value])],
            'is_mandatory' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
