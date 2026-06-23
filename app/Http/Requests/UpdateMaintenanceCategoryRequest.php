<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceUrgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceCategoryRequest extends FormRequest
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
            'parent_id' => ['nullable', 'integer', 'exists:maintenance_categories,id'],
            'code' => ['required', 'string', 'max:80', Rule::unique('maintenance_categories', 'code')->ignore($this->route('maintenanceCategory'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'default_urgency' => ['nullable', Rule::enum(MaintenanceUrgency::class)],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
