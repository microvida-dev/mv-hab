<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceInterventionRequest extends FormRequest
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
            'scheduled_for' => ['nullable', 'date'],
            'performed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'maintenance_supplier_id' => ['nullable', 'integer', 'exists:maintenance_suppliers,id'],
            'work_description' => ['nullable', 'string', 'max:10000'],
            'materials_used' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
