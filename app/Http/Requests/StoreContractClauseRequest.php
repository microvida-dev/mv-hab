<?php

namespace App\Http\Requests;

use App\Enums\ContractClauseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractClauseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'required_without:contest_id', 'exists:programs,id'],
            'contest_id' => ['nullable', 'required_without:program_id', 'exists:contests,id'],
            'code' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10', 'max:20000'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::enum(ContractClauseStatus::class)],
            'is_mandatory' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
