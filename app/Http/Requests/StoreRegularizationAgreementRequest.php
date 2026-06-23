<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegularizationAgreementRequest extends FormRequest
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
            'tenant_financial_account_id' => ['required', 'integer', 'exists:tenant_financial_accounts,id'],
            'arrear_ids' => ['nullable', 'array'],
            'arrear_ids.*' => ['integer', 'exists:arrears,id'],
            'total_amount' => ['nullable', 'numeric', 'min:0.01'],
            'initial_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'installment_count' => ['required', 'integer', 'between:1,60'],
            'periodicity' => ['nullable', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
