<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiValidation;
use Illuminate\Foundation\Http\FormRequest;

class MarkDocumentAiValidationReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $validation = $this->route('validation');

        return $validation instanceof DocumentAiValidation
            && ($this->user()?->can('markManualReview', $validation) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
