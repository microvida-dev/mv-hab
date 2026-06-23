<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkContractDepositPaidRequest extends FormRequest
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
            'paid_at' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'receipt_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
