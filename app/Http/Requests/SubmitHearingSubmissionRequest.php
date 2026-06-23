<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitHearingSubmissionRequest extends FormRequest
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
            'submission_text' => ['required', 'string', 'min:10', 'max:10000'],
            'document_submission_id' => ['nullable', 'exists:document_submissions,id'],
        ];
    }
}
