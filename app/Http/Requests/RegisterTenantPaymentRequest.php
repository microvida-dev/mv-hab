<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantPaymentRequest extends FormRequest
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
            'tenant_invoice_id' => ['required', 'integer', 'exists:tenant_invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_date' => ['required', 'date'],
            'value_date' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:80'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:4000'],
            'confirm_now' => ['sometimes', 'boolean'],
        ];
    }
}
