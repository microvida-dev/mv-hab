<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreControlledWithdrawalRequest extends FormRequest
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
            'application_id' => ['required', 'exists:applications,id'],
            'reason' => ['required', 'string', 'min:10', 'max:3000'],
            'consequence_acknowledged' => ['accepted'],
        ];
    }
}
