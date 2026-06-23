<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDocumentDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('documents', 'export') || $this->user()?->hasPermissionTo('documents', 'view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'include_rejected' => ['nullable', 'boolean'],
            'include_expired' => ['nullable', 'boolean'],
            'export_format' => ['nullable', 'string', 'in:html,pdf,zip'],
        ];
    }
}
