<?php

namespace Database\Seeders\Production;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\ContestStatus;
use App\Enums\MunicipalityOnboardingStatus;
use App\Enums\ProgramStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryProfileStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\Program;
use App\Models\User;
use App\Services\Regulatory\MunicipalRegulatoryOverlayService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AlcanenaRegulatoryProfileSeeder extends Seeder
{
    public const NATIONAL_PAA_CODE = 'NATIONAL-PAA-2019';

    public const NATIONAL_PAA_VERSION = '2019.1-2024.1';

    public const PROFILE_CODE = 'ALCANENA-PAA-2024';

    public const PROFILE_VERSION = '2024.1';

    public function run(): void
    {
        DB::transaction(function (): void {
            $municipality = $this->municipality();
            [$platformActor, $municipalAdministrator] = $this->onboardingActors($municipality);
            [$program, $contest] = $this->programAndContest($municipality);

            $nationalProfile = $this->nationalPaaProfile($platformActor);
            $municipalProfile = $this->municipalProfile(
                $municipality,
                $nationalProfile,
                $municipalAdministrator,
            );

            app(MunicipalRegulatoryOverlayService::class)->assertValid($municipalProfile);

            $this->attachProfile($program, $contest, $municipalProfile);
        }, 3);

        $this->command->info('Perfil regulamentar de Alcanena associado em modo fail-closed.');
    }

    private function municipality(): Municipality
    {
        $municipality = Municipality::query()
            ->where('code', AlcanenaProductionSeeder::MUNICIPALITY_CODE)
            ->lockForUpdate()
            ->first();

        if (! $municipality instanceof Municipality) {
            throw new DomainException('O Município de Alcanena não existe.');
        }

        if (! $municipality->active) {
            throw new DomainException('O Município de Alcanena encontra-se inativo.');
        }

        return $municipality;
    }

    /** @return array{0: User, 1: User} */
    private function onboardingActors(Municipality $municipality): array
    {
        $run = MunicipalityOnboardingRun::query()
            ->where('municipality_code', AlcanenaProductionSeeder::MUNICIPALITY_CODE)
            ->where('municipality_id', $municipality->id)
            ->where('status', MunicipalityOnboardingStatus::Completed->value)
            ->whereNotNull('admin_user_id')
            ->latest('id')
            ->first();

        if (! $run instanceof MunicipalityOnboardingRun) {
            throw new DomainException('Não existe onboarding municipal concluído para o Município de Alcanena.');
        }

        $platformActor = User::query()->find($run->actor_id);
        $municipalAdministrator = User::query()->find($run->admin_user_id);

        if (! $platformActor instanceof User
            || $platformActor->municipality_id !== null
            || $platformActor->status !== 'active') {
            throw new DomainException('O ator global do onboarding não é elegível.');
        }

        if (! $municipalAdministrator instanceof User
            || (int) $municipalAdministrator->municipality_id !== (int) $municipality->id
            || $municipalAdministrator->status !== 'active') {
            throw new DomainException('O administrador municipal de Alcanena não é elegível.');
        }

        return [$platformActor, $municipalAdministrator];
    }

    /** @return array{0: Program, 1: Contest} */
    private function programAndContest(Municipality $municipality): array
    {
        $program = Program::withTrashed()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->first();

        if (! $program instanceof Program || $program->trashed()) {
            throw new DomainException('O Programa de produção de Alcanena não está disponível.');
        }

        if ((int) $program->municipality_id !== (int) $municipality->id) {
            throw new DomainException('O Programa de produção de Alcanena pertence a outro Município.');
        }

        $contest = Contest::withTrashed()
            ->where('code', AlcanenaProductionSeeder::CONTEST_CODE)
            ->first();

        if (! $contest instanceof Contest || $contest->trashed()) {
            throw new DomainException('O Concurso de produção de Alcanena não está disponível.');
        }

        if ((int) $contest->program_id !== (int) $program->id) {
            throw new DomainException('O Concurso de produção de Alcanena não pertence ao Programa esperado.');
        }

        return [$program, $contest];
    }

    private function nationalPaaProfile(User $actor): AffordableRentRegulatoryProfile
    {
        $profile = AffordableRentRegulatoryProfile::withTrashed()
            ->where('code', self::NATIONAL_PAA_CODE)
            ->where('version', self::NATIONAL_PAA_VERSION)
            ->first();

        if ($profile instanceof AffordableRentRegulatoryProfile) {
            if ($profile->trashed()) {
                throw new DomainException('O perfil nacional PAA existe, mas encontra-se eliminado.');
            }

            if ($profile->municipality_id !== null
                || $profile->parent_profile_id !== null
                || $profile->legal_regime !== AffordableRentLegalRegime::PaaLegacy2019) {
                throw new DomainException('O perfil nacional PAA existente é estruturalmente incompatível.');
            }

            $this->assertNonDemoProfile($profile);

            return $profile;
        }

        return AffordableRentRegulatoryProfile::query()->create([
            'municipality_id' => null,
            'parent_profile_id' => null,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'code' => self::NATIONAL_PAA_CODE,
            'version' => self::NATIONAL_PAA_VERSION,
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
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function municipalProfile(
        Municipality $municipality,
        AffordableRentRegulatoryProfile $parent,
        User $actor,
    ): AffordableRentRegulatoryProfile {
        $profile = AffordableRentRegulatoryProfile::withTrashed()
            ->where('code', self::PROFILE_CODE)
            ->where('version', self::PROFILE_VERSION)
            ->first();

        if ($profile instanceof AffordableRentRegulatoryProfile) {
            if ($profile->trashed()) {
                throw new DomainException('O perfil regulamentar municipal de Alcanena existe, mas encontra-se eliminado.');
            }

            if ((int) $profile->municipality_id !== (int) $municipality->id
                || (int) $profile->parent_profile_id !== (int) $parent->id
                || $profile->legal_regime !== AffordableRentLegalRegime::PaaLegacy2019) {
                throw new DomainException('O perfil regulamentar municipal de Alcanena é estruturalmente incompatível.');
            }

            $this->assertNonDemoProfile($profile);

            return $profile;
        }

        return AffordableRentRegulatoryProfile::query()->create([
            'municipality_id' => $municipality->id,
            'parent_profile_id' => $parent->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'code' => self::PROFILE_CODE,
            'version' => self::PROFILE_VERSION,
            'name' => 'Regime Municipal de Arrendamento Acessível — Alcanena',
            'legal_basis' => 'Regulamento Municipal de Arrendamento Acessível de Alcanena — Edital n.º 1820/2024; aplicação do Programa de Arrendamento Acessível nos termos da legislação nacional aplicável.',
            'effective_from' => CarbonImmutable::create(2024, 12, 6),
            'effective_until' => CarbonImmutable::create(2026, 8, 31),
            'status' => RegulatoryProfileStatus::Active,
            'configuration_status' => RegulatoryConfigurationStatus::Incomplete,
            'official_source' => 'Diário da República, 2.ª série, n.º 236, de 05-12-2024 — Edital n.º 1820/2024.',
            'publication_reference' => 'Edital n.º 1820/2024',
            'source_version' => 'edital-1820-2024-dr-236-2024-12-05',
            'maximum_effort_rate_percentage' => '35.00',
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
            'minimum_contract_months' => 60,
            'standard_contract_months' => 60,
            'rent_limits_configured' => false,
            'eligibility_rules_configured' => true,
            'typology_rules_configured' => true,
            'contract_terms_configured' => true,
            'metadata' => [
                'catalogue_type' => 'municipal_overlay',
                'demo' => false,
                'regulation_reference' => 'Edital n.º 1820/2024',
                'regulation_effective_from' => '2024-12-06',
                'maximum_effort_rate_source' => 'Artigos 5.º e 10.º',
                'standard_contract_months_source' => 'Artigo 3.º, n.º 2',
                'temporary_contract_minimum_months_source' => 'Artigo 3.º, n.º 3 — exceção de 9 meses não representada no parâmetro geral; requer regra contratual específica antes de utilização',
                'document_checklist_source' => 'Artigo 12.º',
                'retention_source' => 'Artigo 34.º, n.º 6',
                'rent_table_status' => 'missing_verified_official_source',
                'irs_sixth_bracket_status' => 'missing_verified_official_source',
                'rmmg_status' => 'resolve_at_application_reference_date',
                'contest_notice_status' => 'provisional',
                'publication_blocked' => true,
            ],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function attachProfile(
        Program $program,
        Contest $contest,
        AffordableRentRegulatoryProfile $profile,
    ): void {
        if ($program->status !== ProgramStatus::Draft || $program->published_at !== null) {
            throw new DomainException('O Programa de Alcanena deixou de estar em rascunho; associação automática recusada.');
        }

        if ($contest->status !== ContestStatus::Draft || $contest->published_at !== null) {
            throw new DomainException('O Concurso de Alcanena deixou de estar em rascunho; associação automática recusada.');
        }

        if ($program->regulatory_profile_id !== null
            && (int) $program->regulatory_profile_id !== (int) $profile->id) {
            throw new DomainException('O Programa de Alcanena já possui outro perfil regulamentar.');
        }

        if ($program->legal_regime !== null
            && $program->legal_regime !== $profile->legal_regime) {
            throw new DomainException('O Programa de Alcanena já possui outro regime legal.');
        }

        if ($contest->regulatory_profile_id !== null
            && (int) $contest->regulatory_profile_id !== (int) $profile->id) {
            throw new DomainException('O Concurso de Alcanena já possui outro perfil regulamentar.');
        }

        if ($contest->legal_regime !== null
            && $contest->legal_regime !== $profile->legal_regime) {
            throw new DomainException('O Concurso de Alcanena já possui outro regime legal.');
        }

        if ($program->regulatory_profile_id === null || $program->legal_regime === null) {
            $program->forceFill([
                'regulatory_profile_id' => $profile->id,
                'legal_regime' => $profile->legal_regime,
            ])->save();
        }

        if ($contest->regulatory_profile_id === null || $contest->legal_regime === null) {
            $contest->forceFill([
                'regulatory_profile_id' => $profile->id,
                'legal_regime' => $profile->legal_regime,
            ])->save();
        }
    }

    private function assertNonDemoProfile(AffordableRentRegulatoryProfile $profile): void
    {
        $metadata = $profile->metadata ?? [];
        $source = strtolower((string) $profile->official_source);
        $reference = strtolower((string) $profile->publication_reference);

        if ((bool) data_get($metadata, 'demo', false)
            || (bool) data_get($metadata, 'demo_only', false)
            || str_contains($source, 'fict')
            || str_contains($reference, 'demo')
            || $profile->sixth_irs_bracket_upper_limit === '999999.00') {
            throw new DomainException('Foi detetada configuração regulamentar de demonstração num perfil de produção.');
        }
    }
}
