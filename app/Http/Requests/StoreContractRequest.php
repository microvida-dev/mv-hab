<?php

namespace App\Http\Requests;

use App\Enums\ContractStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
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
            'citizen_id' => ['required', 'exists:citizens,id'],
            'housing_unit_id' => ['required', 'exists:housing_units,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ContractStatus::class)],
        ];
    }
}
