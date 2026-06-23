<?php

namespace App\Http\Requests;

use App\Enums\InspectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInspectionChecklistTemplateRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:80', Rule::unique('inspection_checklist_templates', 'code')->ignore($this->route('inspectionChecklistTemplate'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'inspection_type' => ['nullable', Rule::enum(InspectionType::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
