<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceAssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceAssignmentRequest extends FormRequest
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
            'maintenance_request_id' => ['nullable', 'integer', 'exists:maintenance_requests,id'],
            'assignment_type' => ['required', Rule::enum(MaintenanceAssignmentType::class)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'maintenance_supplier_id' => ['nullable', 'integer', 'exists:maintenance_suppliers,id'],
            'assignment_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
