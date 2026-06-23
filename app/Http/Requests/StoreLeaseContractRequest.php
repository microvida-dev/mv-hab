<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaseContractRequest extends FormRequest
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
            'allocation_id' => ['required', 'exists:allocations,id'],
            'rent_calculation_id' => ['required', 'exists:rent_calculations,id'],
            'contract_template_id' => ['required', 'exists:contract_templates,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'special_conditions' => ['nullable', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
