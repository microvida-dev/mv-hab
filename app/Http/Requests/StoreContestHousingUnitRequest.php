<?php

namespace App\Http\Requests;

use App\Enums\ContestHousingUnitStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContestHousingUnitRequest extends FormRequest
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
            'housing_unit_id' => ['required', 'exists:housing_units,id'],
            'status' => ['nullable', 'string', Rule::in(ContestHousingUnitStatus::values())],
            'availability_starts_at' => ['nullable', 'date'],
            'availability_ends_at' => ['nullable', 'date', 'after_or_equal:availability_starts_at'],
            'typology' => ['nullable', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_occupants' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_occupants' => ['nullable', 'integer', 'min:1', 'max:50'],
            'accessible' => ['boolean'],
            'reserved_for_special_condition' => ['nullable', 'string', 'max:255'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'estimated_expenses' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
