<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\DocumentAiValidationRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterDocumentAiValidationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', DocumentAiValidationRun::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(DocumentAiValidationStatus::class)],
            'severity' => ['nullable', Rule::enum(DocumentAiValidationSeverity::class)],
            'group' => ['nullable', Rule::enum(DocumentAiValidationGroup::class)],
            'requires_review' => ['nullable', 'boolean'],
            'application' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
