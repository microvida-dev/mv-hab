<?php

namespace App\Http\Requests;

use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('documentType')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $documentType = $this->route('documentType');

        return [
            'code' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('document_types', 'code')->ignore($documentType)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'applies_to' => ['required', Rule::enum(DocumentAppliesTo::class)],
            'is_active' => ['sometimes', 'boolean'],
            'is_required_by_default' => ['sometimes', 'boolean'],
            'requires_expiry_date' => ['sometimes', 'boolean'],
            'requires_issue_date' => ['sometimes', 'boolean'],
            'allowed_mime_types' => ['nullable', 'array'],
            'allowed_mime_types.*' => ['string', 'max:120', Rule::in([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ])],
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:25'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
