<?php

namespace App\Http\Requests;

use App\Models\GeneratedOfficialDocument;
use Illuminate\Foundation\Http\FormRequest;

class GenerateOfficialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'createBackoffice',
            GeneratedOfficialDocument::class,
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_template_id' => ['required', 'exists:document_templates,id'],
            'recipient_user_id' => ['nullable', 'exists:users,id'],
            'variables' => ['nullable', 'array'],
            'issue_immediately' => ['sometimes', 'boolean'],
        ];
    }
}
