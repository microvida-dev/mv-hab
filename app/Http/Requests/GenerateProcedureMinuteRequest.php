<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProcedureMinuteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('documents', 'create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contest_id' => ['nullable', 'exists:contests,id'],
            'application_id' => ['nullable', 'exists:applications,id'],
            'procedure_template_id' => ['required', 'exists:procedure_templates,id'],
            'meeting_date' => ['nullable', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'meeting_location' => ['nullable', 'string', 'max:255'],
            'municipal_registry_number' => ['nullable', 'string', 'max:120'],
            'municipal_process_number' => ['nullable', 'string', 'max:120'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'legal_basis' => ['nullable', 'string', 'max:2000'],
            'deliberation_text' => ['nullable', 'string', 'max:5000'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:180'],
        ];
    }
}
