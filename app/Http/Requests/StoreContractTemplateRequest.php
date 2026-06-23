<?php

namespace App\Http\Requests;

use App\Enums\ContractTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(ContractTemplateStatus::class)],
            'version_number' => ['nullable', 'integer', 'min:1'],
            'template_body' => ['required', 'string', 'min:50'],
            'header_html' => ['nullable', 'string', 'max:10000'],
            'footer_html' => ['nullable', 'string', 'max:10000'],
            'clause_ids' => ['nullable', 'array'],
            'clause_ids.*' => ['integer', 'exists:contract_clauses,id'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
