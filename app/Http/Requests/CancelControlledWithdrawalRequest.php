<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelControlledWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
