<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptAllocationOfferRequest extends FormRequest
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
            'candidate_response' => ['nullable', 'string', 'max:3000'],
            'confirm_acceptance' => ['accepted'],
        ];
    }
}
