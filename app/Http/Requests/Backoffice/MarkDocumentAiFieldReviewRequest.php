<?php

namespace App\Http\Requests\Backoffice;

use App\Models\DocumentAiField;
use Illuminate\Foundation\Http\FormRequest;

class MarkDocumentAiFieldReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $field = $this->route('field');

        return $field instanceof DocumentAiField
            && ($this->user()?->can('markForReview', $field) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
