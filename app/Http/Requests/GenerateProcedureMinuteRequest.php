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
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:180'],
        ];
    }
}
