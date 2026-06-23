<?php

namespace App\Http\Requests\Backoffice;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Models\DocumentAiAnalysis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterDocumentAiClassificationsRequest extends FormRequest
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
            'classification_status' => ['nullable', Rule::enum(DocumentAiClassificationStatus::class)],
            'ocr_available' => ['nullable', 'boolean'],
            'requires_manual_review' => ['nullable', 'boolean'],
            'min_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_confidence' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
