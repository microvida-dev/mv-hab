<?php

namespace App\Http\Requests;

use App\Enums\ChargeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunTenantChargeRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['administrator', 'financial_manager', 'municipal_technician']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'charge_type' => ['required', Rule::in(ChargeType::values())],
        ];
    }
}
