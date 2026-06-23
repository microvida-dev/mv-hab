<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeasePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:80'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_now' => ['sometimes', 'boolean'],
        ];
    }
}
