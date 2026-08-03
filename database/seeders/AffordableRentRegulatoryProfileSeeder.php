<?php

namespace Database\Seeders;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AffordableRentRegulatoryProfileSeeder extends Seeder
{
    public const PAA_NATIONAL_CODE = 'NATIONAL-PAA-2019';

    public const RSAA_NATIONAL_CODE = 'NATIONAL-RSAA-2026';

    public function run(): void
    {
        $actorId = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->value('id');

        AffordableRentRegulatoryProfile::withTrashed()->updateOrCreate(
            [
                'code' => self::PAA_NATIONAL_CODE,
                'version' => '2019.1-2024.1',
            ],
            [
                'municipality_id' => null,
                'parent_profile_id' => null,
                'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
                'name' => 'Programa de Arrendamento Acessível — perfil nacional legacy',
                'legal_basis' => 'Decreto-Lei n.º 68/2019; Portaria n.º 175/2019, na redação da Portaria n.º 52/2024.',
                'effective_from' => CarbonImmutable::create(2019, 7, 1),
                'effective_until' => CarbonImmutable::create(2026, 8, 31),
                'status' => RegulatoryProfileStatus::Active,
                'configuration_status' => RegulatoryConfigurationStatus::Incomplete,
                'official_source' => 'Diário da República — diplomas PAA identificados na documentação jurídica do projeto.',
                'publication_reference' => 'DL 68/2019; Portaria 175/2019; Portaria 52/2024',
                'source_version' => 'paa-2019-portaria-52-2024',
                'maximum_effort_rate_percentage' => null,
                'minimum_adult_monthly_income' => null,
                'annual_income_base_limit' => '38632.00',
                'second_person_increment' => '10000.00',
                'additional_person_increment' => '5000.00',
                'tax_year' => null,
                'sixth_irs_bracket_upper_limit' => null,
                'irs_source_reference' => null,
                'irs_source_version' => null,
                'irs_effective_from' => null,
                'irs_effective_until' => null,
                'minimum_contract_months' => null,
                'standard_contract_months' => null,
                'rent_limits_configured' => false,
                'eligibility_rules_configured' => true,
                'typology_rules_configured' => true,
                'contract_terms_configured' => true,
                'metadata' => [
                    'catalogue_type' => 'national',
                    'demo' => false,
                    'rent_table_status' => 'missing_verified_official_source',
                    'irs_sixth_bracket_status' => 'missing_verified_official_source',
                    'publication_blocked' => true,
                ],
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'deleted_at' => null,
            ],
        );

        AffordableRentRegulatoryProfile::withTrashed()->updateOrCreate(
            [
                'code' => self::RSAA_NATIONAL_CODE,
                'version' => '2026.1-incomplete',
            ],
            [
                'municipality_id' => null,
                'parent_profile_id' => null,
                'legal_regime' => AffordableRentLegalRegime::Rsaa2026,
                'name' => 'Regime do Arrendamento Acessível — configuração pendente',
                'legal_basis' => 'Base legal e tabela oficial RSAA pendentes de configuração validada no projeto.',
                'effective_from' => CarbonImmutable::create(2026, 9, 1),
                'effective_until' => null,
                'status' => RegulatoryProfileStatus::Active,
                'configuration_status' => RegulatoryConfigurationStatus::Incomplete,
                'official_source' => null,
                'publication_reference' => null,
                'source_version' => null,
                'maximum_effort_rate_percentage' => null,
                'minimum_adult_monthly_income' => null,
                'annual_income_base_limit' => null,
                'second_person_increment' => null,
                'additional_person_increment' => null,
                'tax_year' => null,
                'sixth_irs_bracket_upper_limit' => null,
                'irs_source_reference' => null,
                'irs_source_version' => null,
                'irs_effective_from' => null,
                'irs_effective_until' => null,
                'minimum_contract_months' => null,
                'standard_contract_months' => null,
                'rent_limits_configured' => false,
                'eligibility_rules_configured' => false,
                'typology_rules_configured' => false,
                'contract_terms_configured' => false,
                'metadata' => [
                    'catalogue_type' => 'national',
                    'demo' => false,
                    'rent_table_status' => 'missing_official_source',
                    'publication_blocked' => true,
                ],
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'deleted_at' => null,
            ],
        );
    }
}
