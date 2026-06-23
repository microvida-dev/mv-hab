<?php

namespace App\Http\Requests;

use App\Enums\IncomeChangeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncomeChangeDeclarationRequest extends FormRequest
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
            'change_type' => ['nullable', Rule::in(IncomeChangeType::values())],
            'changed_at' => ['nullable', 'date'],
            'monthly_income_before' => ['nullable', 'numeric', 'min:0'],
            'monthly_income_after' => ['nullable', 'numeric', 'min:0'],
            'declared_reason' => ['required', 'string', 'max:3000'],
            'candidate_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
