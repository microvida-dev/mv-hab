<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypologyAdequacyRuleRequest extends FormRequest
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
            'program_id' => ['nullable', 'exists:programs,id', 'required_without:contest_id'],
            'contest_id' => ['nullable', 'exists:contests,id', 'required_without:program_id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['boolean'],
            'min_household_members' => ['nullable', 'integer', 'min:1', 'max:50'],
            'max_household_members' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_adults' => ['nullable', 'integer', 'min:0', 'max:50'],
            'max_adults' => ['nullable', 'integer', 'min:0', 'max:50'],
            'min_children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'max_children' => ['nullable', 'integer', 'min:0', 'max:50'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'typology' => ['nullable', 'string', 'max:100'],
            'requires_accessibility' => ['boolean'],
            'special_condition_key' => ['nullable', 'string', 'max:255'],
            'priority_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
