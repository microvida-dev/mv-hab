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

            'minute_sequence' => ['nullable', 'integer', 'min:1', 'max:999'],
            'meeting_date_long' => ['nullable', 'string', 'max:180'],
            'meeting_time_long' => ['nullable', 'string', 'max:80'],
            'jury_appointment_reference' => ['nullable', 'string', 'max:255'],
            'opening_notice_number' => ['nullable', 'string', 'max:120'],
            'opening_notice_date' => ['nullable', 'date'],
            'submission_platform_url' => ['nullable', 'string', 'max:255'],
            'document_completion_deadline' => ['nullable', 'date'],
            'exceptional_application_text' => ['nullable', 'string', 'max:2000'],
            'preference_instruction_text' => ['nullable', 'string', 'max:2000'],

            'subject' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:180'],

            'jury_president_name' => ['nullable', 'string', 'max:180'],
            'jury_president_role' => ['nullable', 'string', 'max:255'],

            'jury_vogal_1_name' => ['nullable', 'string', 'max:180'],
            'jury_vogal_1_role' => ['nullable', 'string', 'max:255'],
            'jury_vogal_2_name' => ['nullable', 'string', 'max:180'],
            'jury_vogal_2_role' => ['nullable', 'string', 'max:255'],
            'jury_vogal_3_name' => ['nullable', 'string', 'max:180'],
            'jury_vogal_3_role' => ['nullable', 'string', 'max:255'],
        ];
    }
}
