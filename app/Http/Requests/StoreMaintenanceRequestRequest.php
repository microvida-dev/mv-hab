<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceUrgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
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
            'housing_unit_id' => ['nullable', 'integer', 'exists:housing_units,id'],
            'lease_contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'maintenance_category_id' => ['nullable', 'integer', 'exists:maintenance_categories,id'],
            'urgency' => ['required', Rule::enum(MaintenanceUrgency::class)],
            'technical_priority' => ['nullable', Rule::enum(MaintenanceUrgency::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'location_in_property' => ['nullable', 'string', 'max:255'],
            'tenant_availability' => ['nullable', 'string', 'max:3000'],
            'access_instructions' => ['nullable', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
        ];
    }
}
