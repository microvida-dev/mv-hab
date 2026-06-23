<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceDocumentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('replace', $this->route('documentSubmission')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/heic,image/heif', 'mimes:pdf,jpg,jpeg,png,webp,heic,heif'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
