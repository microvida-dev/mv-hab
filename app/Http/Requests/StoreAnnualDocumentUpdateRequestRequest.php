<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnualDocumentUpdateRequestRequest extends FormRequest
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
            'reference_year' => ['nullable', 'integer', 'between:2020,2100'],
            'due_date' => ['nullable', 'date'],
            'required_document_types' => ['nullable', 'array'],
            'required_document_types.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
