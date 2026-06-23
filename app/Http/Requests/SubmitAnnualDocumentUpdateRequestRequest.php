<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnnualDocumentUpdateRequestRequest extends FormRequest
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
            'document_submission_ids' => ['nullable', 'array'],
            'document_submission_ids.*' => ['integer', 'exists:document_submissions,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
