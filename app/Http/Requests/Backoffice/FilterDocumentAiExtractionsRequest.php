<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use App\Models\DocumentAiAnalysis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterDocumentAiExtractionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', DocumentAiAnalysis::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['nullable', Rule::enum(DocumentAiDocumentType::class)],
            'extraction_status' => ['nullable', Rule::enum(DocumentAiExtractionStatus::class)],
            'requires_review' => ['nullable', 'boolean'],
            'field_key' => ['nullable', 'string', 'max:80'],
            'min_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
