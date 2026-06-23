<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefuseAllocationOfferRequest extends FormRequest
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
            'refusal_reason' => ['required', 'string', 'min:5', 'max:3000'],
            'confirm_refusal' => ['accepted'],
        ];
    }
}
