<?php

namespace App\Http\Requests;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
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
            'housing_unit_id' => ['required', 'exists:housing_units,id'],
            'citizen_id' => ['nullable', 'exists:citizens,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::enum(MaintenancePriority::class)],
            'status' => ['required', Rule::enum(MaintenanceRequestStatus::class)],
            'reported_at' => ['required', 'date'],
            'resolved_at' => ['nullable', 'date', 'after_or_equal:reported_at'],
        ];
    }
}
