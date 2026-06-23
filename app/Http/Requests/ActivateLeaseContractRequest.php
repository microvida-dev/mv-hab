<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLeaseContractRequest extends FormRequest
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
            'activation_reason' => ['nullable', 'string', 'max:3000'],
            'confirm_activation' => ['accepted'],
        ];
    }
}
