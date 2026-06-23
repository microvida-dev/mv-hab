<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrativeDecisionRequest extends FormRequest
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
            'summary' => ['required', 'string', 'max:3000'],
            'legal_basis' => ['nullable', 'string', 'max:3000'],
            'grounds' => ['required', 'string', 'max:5000'],
            'candidate_visible' => ['nullable', 'boolean'],
        ];
    }
}
