<?php

namespace App\Http\Requests;

use App\Enums\ConsentLegalBasis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentPurposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()?->hasRole('candidate') && $this->user()?->hasPermission('privacy.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'legal_basis' => ['required', Rule::in(ConsentLegalBasis::values())],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_explicit_consent' => ['sometimes', 'boolean'],
            'retention_period_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
        ];
    }
}
