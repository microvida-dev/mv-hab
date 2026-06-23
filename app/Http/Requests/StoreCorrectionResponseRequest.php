<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCorrectionResponseRequest extends FormRequest
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
            'correction_request_item_id' => ['required', 'integer', 'exists:correction_request_items,id'],
            'response_text' => ['nullable', 'string', 'max:5000'],
            'document_submission_id' => ['nullable', 'integer', 'exists:document_submissions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('response_text') && ! $this->filled('document_submission_id')) {
                $validator->errors()->add('response_text', 'Indique uma resposta escrita ou associe um documento.');
            }
        });
    }
}
