<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceCostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceCostRequest extends FormRequest
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
            'maintenance_intervention_id' => ['nullable', 'integer', 'exists:maintenance_interventions,id'],
            'cost_type' => ['required', Rule::enum(MaintenanceCostType::class)],
            'description' => ['required', 'string', 'max:3000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'supplier_id' => ['nullable', 'integer', 'exists:maintenance_suppliers,id'],
            'maintenance_supplier_id' => ['nullable', 'integer', 'exists:maintenance_suppliers,id'],
            'invoice_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
