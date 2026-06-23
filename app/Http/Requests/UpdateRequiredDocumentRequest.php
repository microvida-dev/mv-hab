<?php

namespace App\Http\Requests;

use App\Enums\DocumentAppliesTo;
use App\Enums\RequiredDocumentConditionOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequiredDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('requiredDocument')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'required_for' => ['required', Rule::enum(DocumentAppliesTo::class)],
            'condition_key' => ['required', 'string', 'max:150'],
            'condition_operator' => ['required', Rule::enum(RequiredDocumentConditionOperator::class)],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
