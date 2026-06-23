<?php

namespace App\Http\Requests;

use App\Enums\RentReviewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentReviewRequest extends FormRequest
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
            'review_type' => ['nullable', Rule::in(RentReviewType::values())],
            'proposed_rent' => ['nullable', 'numeric', 'min:0.01'],
            'effective_from' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
