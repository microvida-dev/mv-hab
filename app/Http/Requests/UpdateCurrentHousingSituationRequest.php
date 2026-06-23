<?php

namespace App\Http\Requests;

use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Http\Requests\Concerns\NormalizesCandidateBooleans;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrentHousingSituationRequest extends FormRequest
{
    use NormalizesCandidateBooleans;

    public function authorize(): bool
    {
        $registration = $this->user()?->adhesionRegistration;

        return $registration !== null && $this->user()->can('update', $registration);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleans([
            'resides_in_municipality',
            'works_in_municipality',
            'is_overcrowded',
            'is_at_risk_of_eviction',
            'is_homeless',
            'is_temporary_accommodation',
            'is_domestic_violence_victim',
            'has_accessibility_needs',
            'has_high_rent_burden',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'housing_status' => ['required', Rule::enum(HousingStatus::class)],
            'current_address' => ['nullable', 'string', 'max:255'],
            'current_postal_code' => ['nullable', 'string', 'max:20'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'current_parish' => ['nullable', 'string', 'max:100'],
            'current_municipality' => ['nullable', 'string', 'max:100'],
            'resides_in_municipality' => ['boolean'],
            'residence_years_in_municipality' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'works_in_municipality' => ['boolean'],
            'workplace_municipality' => ['nullable', 'string', 'max:100'],
            'current_housing_typology' => ['nullable', 'string', 'max:50'],
            'current_housing_rooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'current_housing_condition' => ['nullable', Rule::enum(HousingCondition::class)],
            'current_monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'current_housing_expense' => ['nullable', 'numeric', 'min:0'],
            'is_overcrowded' => ['boolean'],
            'is_at_risk_of_eviction' => ['boolean'],
            'is_homeless' => ['boolean'],
            'is_temporary_accommodation' => ['boolean'],
            'is_domestic_violence_victim' => ['boolean'],
            'has_accessibility_needs' => ['boolean'],
            'has_high_rent_burden' => ['boolean'],
            'request_reason' => ['nullable', 'string', 'max:2000'],
            'additional_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
