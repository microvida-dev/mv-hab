<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrativeWorkflowConfigRequest extends FormRequest
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
            'program_id' => ['nullable', 'integer', 'exists:programs,id', 'required_without:contest_id'],
            'contest_id' => ['nullable', 'integer', 'exists:contests,id', 'required_without:program_id'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'default_correction_deadline_days' => ['required', 'integer', 'min:1', 'max:120'],
            'allow_deadline_extension' => ['nullable', 'boolean'],
            'max_deadline_extensions' => ['nullable', 'integer', 'min:0', 'max:10'],
            'auto_mark_overdue' => ['nullable', 'boolean'],
            'requires_decision_approval' => ['nullable', 'boolean'],
        ];
    }
}
