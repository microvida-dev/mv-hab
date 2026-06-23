<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaseContractRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'special_conditions' => ['nullable', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
