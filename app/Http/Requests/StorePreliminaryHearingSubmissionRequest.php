<?php

namespace App\Http\Requests;

use App\Models\Hearing;
use Illuminate\Foundation\Http\FormRequest;

class StorePreliminaryHearingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $hearing = $this->route('hearing');
        $applicationId = $hearing instanceof Hearing ? $hearing->application_id : null;
        $subject = $hearing instanceof Hearing ? $hearing->subject : null;

        $this->merge([
            'application_id' => $this->input('application_id') ?? $applicationId,
            'subject' => $this->input('subject') ?? $subject,
            'body' => $this->input('body') ?? $this->input('submission_text'),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['required', 'exists:applications,id'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:10', 'max:10000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'document_submission_id' => ['nullable', 'exists:document_submissions,id'],
        ];
    }
}
