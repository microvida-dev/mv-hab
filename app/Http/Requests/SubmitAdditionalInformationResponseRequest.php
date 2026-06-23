<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAdditionalInformationResponseRequest extends FormRequest
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
            'response_text' => ['nullable', 'string', 'max:10000', 'required_without:document_submission_id'],
            'document_submission_id' => ['nullable', 'exists:document_submissions,id', 'required_without:response_text'],
        ];
    }
}
