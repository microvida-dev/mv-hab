<?php

namespace Database\Factories;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffordableRentRegulatoryProfile>
 */
class AffordableRentRegulatoryProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => null,
            'parent_profile_id' => null,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'code' => 'PAA-'.fake()->unique()->bothify('????-####'),
            'version' => '1.0',
            'name' => 'Perfil regulamentar PAA de teste',
            'legal_basis' => 'Fonte oficial de teste sem valor jurídico.',
            'effective_from' => '2019-07-01',
            'effective_until' => '2026-08-31',
            'status' => RegulatoryProfileStatus::Active,
            'configuration_status' => RegulatoryConfigurationStatus::Complete,
            'official_source' => 'Fonte oficial de teste',
            'publication_reference' => 'TESTE/1.0',
            'source_version' => '1.0',
            'maximum_effort_rate_percentage' => '35.00',
            'minimum_adult_monthly_income' => null,
            'annual_income_base_limit' => '38632.00',
            'second_person_increment' => '10000.00',
            'additional_person_increment' => '5000.00',
            'tax_year' => 2026,
            'sixth_irs_bracket_upper_limit' => '999999.00',
            'irs_source_reference' => 'TESTE-SEM-VALOR-JURIDICO',
            'irs_source_version' => 'test-fixture-2026',
            'irs_effective_from' => '2026-01-01',
            'irs_effective_until' => '2026-12-31',
            'minimum_contract_months' => null,
            'standard_contract_months' => null,
            'rent_limits_configured' => true,
            'eligibility_rules_configured' => true,
            'typology_rules_configured' => true,
            'contract_terms_configured' => true,
            'metadata' => [
                'test_data' => true,
                'demo' => true,
                'demo_only' => true,
            ],
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function rsaaIncomplete(): static
    {
        return $this->state(fn () => [
            'legal_regime' => AffordableRentLegalRegime::Rsaa2026,
            'code' => 'RSAA-'.fake()->unique()->bothify('????-####'),
            'name' => 'Perfil RSAA incompleto de teste',
            'effective_from' => '2026-09-01',
            'effective_until' => null,
            'configuration_status' => RegulatoryConfigurationStatus::Incomplete,
            'rent_limits_configured' => false,
            'tax_year' => null,
            'sixth_irs_bracket_upper_limit' => null,
            'irs_source_reference' => null,
            'irs_source_version' => null,
            'irs_effective_from' => null,
            'irs_effective_until' => null,
        ]);
    }
}
