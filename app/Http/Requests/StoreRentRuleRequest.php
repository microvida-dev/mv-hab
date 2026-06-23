<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentRuleRequest extends FormRequest
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
            'rent_rule_set_id' => ['required', 'exists:rent_rule_sets,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'rule_type' => ['required', 'string', 'max:100'],
            'operator' => ['nullable', 'string', 'max:100'],
            'minimum_value' => ['nullable', 'numeric'],
            'maximum_value' => ['nullable', 'numeric'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_result' => ['nullable', 'numeric', 'min:0'],
            'maximum_result' => ['nullable', 'numeric', 'min:0'],
            'priority_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
