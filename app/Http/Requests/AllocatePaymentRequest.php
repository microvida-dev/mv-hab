<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AllocatePaymentRequest extends FormRequest
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
            'rent_installment_id' => ['nullable', 'integer', 'exists:rent_installments,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'allocate_oldest' => ['sometimes', 'boolean'],
        ];
    }
}
