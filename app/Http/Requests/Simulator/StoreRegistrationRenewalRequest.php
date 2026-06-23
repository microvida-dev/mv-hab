<?php

namespace App\Http\Requests\Simulator;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('candidate') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:120'],
        ];
    }
}
