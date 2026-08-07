<?php

namespace App\Http\Requests;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Models\AffordableRentRegulatoryProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegulatoryProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBackoffice', AffordableRentRegulatoryProfile::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->profileRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function profileRules(): array
    {
        return [
            'scope_type' => ['required', Rule::in(['national', 'municipal'])],
            'parent_profile_id' => ['nullable', 'integer', 'exists:affordable_rent_regulatory_profiles,id'],
            'legal_regime' => ['required', Rule::enum(AffordableRentLegalRegime::class)],
            'code' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:255'],
            'legal_basis' => ['required', 'string'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'configuration_status' => ['required', Rule::enum(RegulatoryConfigurationStatus::class)],
            'official_source' => ['nullable', 'string'],
            'publication_reference' => ['nullable', 'string', 'max:255'],
            'source_version' => ['nullable', 'string', 'max:255'],
            'maximum_effort_rate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_adult_monthly_income' => ['nullable', 'numeric', 'min:0'],
            'annual_income_base_limit' => ['nullable', 'numeric', 'min:0'],
            'second_person_increment' => ['nullable', 'numeric', 'min:0'],
            'additional_person_increment' => ['nullable', 'numeric', 'min:0'],
            'tax_year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'sixth_irs_bracket_upper_limit' => ['nullable', 'numeric', 'min:0'],
            'irs_source_reference' => ['nullable', 'string', 'max:255'],
            'irs_source_version' => ['nullable', 'string', 'max:120'],
            'irs_effective_from' => ['nullable', 'date'],
            'irs_effective_until' => ['nullable', 'date', 'after_or_equal:irs_effective_from'],
            'minimum_contract_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'standard_contract_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'rent_limits_configured' => ['sometimes', 'boolean'],
            'eligibility_rules_configured' => ['sometimes', 'boolean'],
            'typology_rules_configured' => ['sometimes', 'boolean'],
            'contract_terms_configured' => ['sometimes', 'boolean'],
        ];
    }
}
