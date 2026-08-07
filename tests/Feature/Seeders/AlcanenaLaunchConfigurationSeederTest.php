<?php

namespace Tests\Feature\Seeders;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\ConsentLegalBasis;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentCategory;
use App\Enums\MunicipalityOnboardingStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RetentionAction;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\ConsentPurpose;
use App\Models\Contest;
use App\Models\DocumentType;
use App\Models\Municipality;
use App\Models\MunicipalityOnboardingRun;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\RetentionPolicy;
use App\Models\User;
use Database\Seeders\Production\AlcanenaLaunchConfigurationSeeder;
use Database\Seeders\Production\AlcanenaProductionSeeder;
use Database\Seeders\Production\AlcanenaRegulatoryProfileSeeder;
use Database\Seeders\Production\AlcanenaRgpdSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlcanenaLaunchConfigurationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_the_alcanena_launch_configuration_without_publishing(): void
    {
        [$municipality] = $this->onboardedMunicipality();

        $this->seed(AlcanenaLaunchConfigurationSeeder::class);

        $program = Program::query()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->sole();
        $contest = Contest::query()
            ->where('code', AlcanenaProductionSeeder::CONTEST_CODE)
            ->sole();
        $profile = AffordableRentRegulatoryProfile::query()
            ->where('code', AlcanenaRegulatoryProfileSeeder::PROFILE_CODE)
            ->where('version', AlcanenaRegulatoryProfileSeeder::PROFILE_VERSION)
            ->sole();

        $this->assertSame($municipality->id, $profile->municipality_id);
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $profile->legal_regime);
        $this->assertSame(RegulatoryConfigurationStatus::Incomplete, $profile->configuration_status);
        $this->assertSame('35.00', $profile->maximum_effort_rate_percentage);
        $this->assertSame(60, $profile->minimum_contract_months);
        $this->assertSame(60, $profile->standard_contract_months);
        $this->assertFalse($profile->rent_limits_configured);
        $this->assertTrue((bool) data_get($profile->metadata, 'publication_blocked'));

        $this->assertSame($profile->id, $program->regulatory_profile_id);
        $this->assertSame($profile->id, $contest->regulatory_profile_id);
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $program->legal_regime);
        $this->assertSame(AffordableRentLegalRegime::PaaLegacy2019, $contest->legal_regime);
        $this->assertNull($program->published_at);
        $this->assertNull($contest->published_at);

        $this->assertSame(27, DocumentType::query()->count());
        $this->assertSame(
            11,
            RequiredDocument::query()
                ->where('program_id', $program->id)
                ->where('contest_id', $contest->id)
                ->count(),
        );

        $planningPurpose = ConsentPurpose::query()
            ->where('code', AlcanenaRgpdSeeder::PLANNING_PURPOSE_CODE)
            ->sole();

        $this->assertSame($municipality->id, $planningPurpose->municipality_id);
        $this->assertSame(ConsentLegalBasis::PublicInterest, $planningPurpose->legal_basis);
        $this->assertTrue($planningPurpose->is_required);
        $this->assertFalse($planningPurpose->requires_explicit_consent);
        $this->assertNull($planningPurpose->retention_period_months);
        $this->assertSame(5, ConsentPurpose::query()->count());

        $retentionPolicy = RetentionPolicy::query()
            ->where('code', AlcanenaRgpdSeeder::RETENTION_POLICY_CODE)
            ->sole();

        $this->assertSame($municipality->id, $retentionPolicy->municipality_id);
        $this->assertSame('draft', $retentionPolicy->status);
        $this->assertSame(60, $retentionPolicy->retention_period_months);
        $this->assertSame(RetentionAction::ReviewManually, $retentionPolicy->retention_action);
        $this->assertTrue($retentionPolicy->requires_manual_approval);
        $this->assertNull($retentionPolicy->approved_at);
    }

    public function test_it_is_idempotent_and_preserves_manual_changes(): void
    {
        $this->onboardedMunicipality();
        $this->seed(AlcanenaLaunchConfigurationSeeder::class);

        $program = Program::query()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->sole();
        $documentType = DocumentType::query()
            ->where('code', 'alcanena_domicilio_fiscal')
            ->sole();
        $planningPurpose = ConsentPurpose::query()
            ->where('code', AlcanenaRgpdSeeder::PLANNING_PURPOSE_CODE)
            ->sole();
        $retentionPolicy = RetentionPolicy::query()
            ->where('code', AlcanenaRgpdSeeder::RETENTION_POLICY_CODE)
            ->sole();

        $program->forceFill(['summary' => 'Resumo municipal validado manualmente.'])->save();
        $documentType->forceFill(['name' => 'Certidão fiscal validada manualmente'])->save();
        $planningPurpose->forceFill(['description' => 'Finalidade municipal validada manualmente.'])->save();
        $retentionPolicy->forceFill(['description' => 'Política municipal validada manualmente.'])->save();

        $this->seed(AlcanenaLaunchConfigurationSeeder::class);

        $this->assertSame('Resumo municipal validado manualmente.', $program->fresh()?->summary);
        $this->assertSame('Certidão fiscal validada manualmente', $documentType->fresh()?->name);
        $this->assertSame('Finalidade municipal validada manualmente.', $planningPurpose->fresh()?->description);
        $this->assertSame('Política municipal validada manualmente.', $retentionPolicy->fresh()?->description);
        $this->assertSame(27, DocumentType::query()->count());
        $this->assertSame(11, RequiredDocument::query()->count());
        $this->assertSame(5, ConsentPurpose::query()->count());
        $this->assertSame(1, RetentionPolicy::query()->count());
        $this->assertSame(
            1,
            AffordableRentRegulatoryProfile::query()
                ->where('code', AlcanenaRegulatoryProfileSeeder::PROFILE_CODE)
                ->where('version', AlcanenaRegulatoryProfileSeeder::PROFILE_VERSION)
                ->count(),
        );
    }

    public function test_it_fails_closed_when_an_alcanena_document_type_is_soft_deleted(): void
    {
        $this->onboardedMunicipality();
        $this->seed(AlcanenaProductionSeeder::class);

        $documentType = DocumentType::query()->create([
            'code' => 'alcanena_domicilio_fiscal',
            'name' => 'Certidão de domicílio fiscal',
            'description' => 'Fixture de conflito.',
            'category' => DocumentCategory::Tax->value,
            'applies_to' => DocumentAppliesTo::HouseholdMember->value,
            'is_active' => true,
            'is_required_by_default' => false,
            'requires_expiry_date' => false,
            'requires_issue_date' => false,
            'allowed_mime_types' => ['application/pdf'],
            'max_file_size_mb' => 10,
            'sort_order' => 1010,
        ]);
        $documentType->delete();

        try {
            $this->seed(AlcanenaLaunchConfigurationSeeder::class);
            $this->fail('Era esperada uma exceção para tipo documental eliminado.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('encontra-se eliminado', $exception->getMessage());
        }

        $this->assertSame(0, AffordableRentRegulatoryProfile::query()->count());
        $this->assertSame(0, RequiredDocument::query()->count());
    }

    public function test_it_fails_closed_when_the_program_has_a_different_regulatory_profile(): void
    {
        [$municipality] = $this->onboardedMunicipality();
        $this->seed(AlcanenaProductionSeeder::class);

        $conflictingProfile = AffordableRentRegulatoryProfile::factory()->create([
            'municipality_id' => $municipality->id,
            'code' => 'ALCANENA-CONFLICTING-PROFILE',
            'version' => '1.0',
        ]);

        $program = Program::query()
            ->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)
            ->sole();
        $program->forceFill([
            'regulatory_profile_id' => $conflictingProfile->id,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
        ])->save();

        $this->expectExceptionMessage('já possui outro perfil regulamentar');

        $this->seed(AlcanenaLaunchConfigurationSeeder::class);
    }

    public function test_it_rejects_demo_regulatory_data_in_the_production_profile_identity(): void
    {
        [$municipality, $platformActor] = $this->onboardedMunicipality();
        $this->seed(AlcanenaProductionSeeder::class);

        $national = AffordableRentRegulatoryProfile::query()->create([
            'municipality_id' => null,
            'parent_profile_id' => null,
            'legal_regime' => AffordableRentLegalRegime::PaaLegacy2019,
            'code' => AlcanenaRegulatoryProfileSeeder::NATIONAL_PAA_CODE,
            'version' => AlcanenaRegulatoryProfileSeeder::NATIONAL_PAA_VERSION,
            'name' => 'Perfil nacional incompatível',
            'legal_basis' => 'Fixture.',
            'effective_from' => '2019-07-01',
            'effective_until' => '2026-08-31',
            'status' => 'active',
            'configuration_status' => 'incomplete',
            'official_source' => 'Dados fictícios de demonstração',
            'publication_reference' => 'DEMO-PAA',
            'source_version' => 'demo',
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
            'metadata' => ['demo' => true, 'demo_only' => true],
            'created_by' => $platformActor->id,
            'updated_by' => $platformActor->id,
        ]);

        $this->assertNull($national->municipality_id);
        $this->assertSame($municipality->id, Program::query()->where('slug', AlcanenaProductionSeeder::PROGRAM_SLUG)->value('municipality_id'));
        $this->expectExceptionMessage('configuração regulamentar de demonstração');

        $this->seed(AlcanenaLaunchConfigurationSeeder::class);
    }

    public function test_it_fails_closed_without_completed_onboarding(): void
    {
        Municipality::factory()->create([
            'code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'active' => true,
        ]);

        $this->expectExceptionMessage('Não existe onboarding municipal concluído');

        $this->seed(AlcanenaLaunchConfigurationSeeder::class);
    }

    /** @return array{0: Municipality, 1: User, 2: User} */
    private function onboardedMunicipality(): array
    {
        $municipality = Municipality::factory()->create([
            'code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'active' => true,
        ]);
        $platformActor = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $municipalAdministrator = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        MunicipalityOnboardingRun::query()->create([
            'operation_id' => (string) Str::uuid(),
            'municipality_code' => AlcanenaProductionSeeder::MUNICIPALITY_CODE,
            'municipality_id' => $municipality->id,
            'actor_id' => $platformActor->id,
            'admin_user_id' => $municipalAdministrator->id,
            'status' => MunicipalityOnboardingStatus::Completed,
            'input_fingerprint' => hash('sha256', 'alcanena-launch-configuration-seeder-test'),
            'role_template_key' => 'municipal-administrator',
            'role_template_version' => 'test-v1',
            'role_template_fingerprint' => hash('sha256', 'municipal-administrator-test-v1'),
            'attempt_count' => 1,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        return [$municipality, $platformActor, $municipalAdministrator];
    }
}
