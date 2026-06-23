<?php

namespace App\Http\Requests;

use App\Enums\InspectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyInspectionRequest extends FormRequest
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
            'housing_unit_id' => ['required', 'integer', 'exists:housing_units,id'],
            'lease_contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'inspection_checklist_template_id' => ['nullable', 'integer', 'exists:inspection_checklist_templates,id'],
            'inspection_type' => ['required', Rule::enum(InspectionType::class)],
            'scheduled_for' => ['nullable', 'date'],
            'inspector_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
