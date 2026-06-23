<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondCorrectionRequestRequest extends FormRequest
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
            'correction_request_id' => ['required', 'exists:correction_requests,id'],
            'correction_request_item_id' => ['required', 'exists:correction_request_items,id'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'response_text' => ['nullable', 'string', 'min:10', 'max:10000'],
            'document_submission_id' => ['nullable', 'exists:document_submissions,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
