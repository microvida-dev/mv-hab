<?php

namespace App\Http\Requests\Simulator\Concerns;

trait ValidatesSimulationInput
{
    /**
     * @return array<string, mixed>
     */
    protected function simulationRules(): array
    {
        return [
            'contest_id' => ['nullable', 'integer', 'exists:contests,id'],
            'household_members_count' => ['required', 'integer', 'min:1', 'max:20'],
            'adults_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'disabled_members_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'monthly_income' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'annual_income' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'current_monthly_rent' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'housing_status' => ['required', 'string', 'max:100'],
            'preferred_parishes' => ['nullable', 'array'],
            'preferred_parishes.*' => ['string', 'max:120'],
            'preferred_typologies' => ['nullable', 'array'],
            'preferred_typologies.*' => ['string', 'max:20'],
            'has_accessibility_needs' => ['nullable', 'boolean'],
            'has_property' => ['nullable', 'boolean'],
            'receives_housing_support' => ['nullable', 'boolean'],
            'has_municipal_debt' => ['nullable', 'boolean'],
            'tax_regularized' => ['nullable', 'boolean'],
            'social_security_regularized' => ['nullable', 'boolean'],
            'has_residence_permit' => ['nullable', 'boolean'],
            'false_declarations_history' => ['nullable', 'boolean'],
            'previous_municipal_eviction' => ['nullable', 'boolean'],
            'privacy_notice_accepted' => ['accepted'],
        ];
    }
}
