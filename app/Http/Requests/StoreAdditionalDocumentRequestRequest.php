<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdditionalDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document_type_id' => ['nullable', 'exists:document_types,id'],
            'required_document_id' => ['nullable', 'exists:required_documents,id'],
            'due_at' => ['nullable', 'date', 'after:now'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
