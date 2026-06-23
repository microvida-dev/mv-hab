<?php

namespace App\Http\Requests;

use App\Enums\ContractValidationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateLeaseContractRequest extends FormRequest
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
            'validation_type' => ['required', Rule::enum(ContractValidationType::class)],
            'summary' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
