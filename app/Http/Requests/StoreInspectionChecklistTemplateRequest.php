<?php

namespace App\Http\Requests;

use App\Enums\InspectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInspectionChecklistTemplateRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:80', 'unique:inspection_checklist_templates,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'inspection_type' => ['nullable', Rule::enum(InspectionType::class)],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.code' => ['nullable', 'string', 'max:80'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.area' => ['nullable', 'string', 'max:255'],
        ];
    }
}
