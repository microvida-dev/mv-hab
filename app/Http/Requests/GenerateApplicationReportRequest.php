<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateApplicationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reports.export') || $this->user()?->hasPermission('reports.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['nullable', 'exists:applications,id'],
            'format' => ['required', 'string', 'in:html,pdf,csv,xlsx'],
            'include_documents' => ['nullable', 'boolean'],
            'include_timeline' => ['nullable', 'boolean'],
            'include_internal_notes' => ['nullable', 'boolean'],
        ];
    }
}
