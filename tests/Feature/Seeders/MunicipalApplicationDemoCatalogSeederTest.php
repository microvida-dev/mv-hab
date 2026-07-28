<?php

namespace Tests\Feature\Seeders;

use App\Enums\AffordableRentLegalRegime;
use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContestStatus;
use App\Enums\DocumentReferencePeriodUnit;
use App\Enums\EligibilityRuleSetStatus;
use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\ProgramStatus;
use App\Enums\PublicVisibilityStatus;
use App\Enums\RegulatoryConfigurationStatus;
use App\Enums\RegulatoryContext;
use App\Enums\RegulatoryProfileStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentLimitConfigurationStatus;
use App\Enums\RentRuleSetStatus;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\RegulatorySnapshot;
use App\Models\RentLimitTableManifest;
use App\Models\RentLimitTableRow;
use App\Models\RentRuleSet;
use App\Models\RequiredDocument;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AffordableRentRegulatoryProfileSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalApplicationDemoCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MVHAB-Demo-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            self::PASSWORD,
        );

        CarbonImmutable::setTestNow(
            $this->referenceDate()->setTime(12, 0),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_seeder_creates_only_the_applicable_demo_paa_overlay(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();

        $profile = AffordableRentRegulatoryProfile::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::PROFILE_CODE,
            )
            ->sole();

        $nationalPaa = AffordableRentRegulatoryProfile::query()
            ->where(
                'code',
                AffordableRentRegulatoryProfileSeeder::PAA_NATIONAL_CODE,
            )
            ->sole();

        $this->assertSame(
            AffordableRentLegalRegime::PaaLegacy2019,
            AffordableRentLegalRegime::forReferenceDate(
                $this->referenceDate(),
            ),
        );

        $this->assertSame(
            $municipality->id,
            $profile->municipality_id,
        );
        $this->assertSame(
            $nationalPaa->id,
            $profile->parent_profile_id,
        );
        $this->assertSame(
            AffordableRentLegalRegime::PaaLegacy2019,
            $profile->legal_regime,
        );
        $this->assertSame(
            RegulatoryProfileStatus::Active,
            $profile->status,
        );
        $this->assertSame(
            RegulatoryConfigurationStatus::Complete,
            $profile->configuration_status,
        );
        $this->assertSame(
            '2026-01-01',
            $profile->effective_from->toDateString(),
        );
        $this->assertSame(
            '2026-08-31',
            $profile->effective_until?->toDateString(),
        );

        $this->assertTrue(
            (bool) data_get(
                $profile->metadata,
                'demo',
            ),
        );
        $this->assertTrue(
            (bool) data_get(
                $profile->metadata,
                'demo_only',
            ),
        );
        $this->assertSame(
            'municipal_overlay',
            data_get(
                $profile->metadata,
                'catalogue_type',
            ),
        );

        $this->assertStringContainsString(
            'fictícios',
            mb_strtolower(
                (string) $profile->official_source,
            ),
        );
        $this->assertStringStartsWith(
            'DEMO-',
            (string) $profile->publication_reference,
        );
        $this->assertStringStartsWith(
            'DEMO-',
            (string) $profile->irs_source_reference,
        );

        $this->assertSame(
            0,
            AffordableRentRegulatoryProfile::query()
                ->where(
                    'municipality_id',
                    $municipality->id,
                )
                ->where(
                    'legal_regime',
                    AffordableRentLegalRegime::Rsaa2026->value,
                )
                ->count(),
        );

        $this->assertNull($nationalPaa->municipality_id);
        $this->assertNotSame(
            RegulatoryConfigurationStatus::Complete,
            $nationalPaa->configuration_status,
        );
    }

    public function test_program_and_contest_use_dates_relative_to_the_demo_reference_date(): void
    {
        $this->seedDemo();

        $profile = $this->demoProfile();
        $municipality = $this->demoMunicipality();
        $actor = $this->demoAnalyst();

        $program = $this->demoProgram();
        $contest = $this->demoContest();

        $this->assertSame(
            $municipality->id,
            $program->municipality_id,
        );
        $this->assertSame(
            $profile->id,
            $program->regulatory_profile_id,
        );
        $this->assertSame(
            AffordableRentLegalRegime::PaaLegacy2019,
            $program->legal_regime,
        );
        $this->assertSame(
            ProgramStatus::Published,
            $program->status,
        );
        $this->assertSame(
            $actor->id,
            $program->created_by,
        );
        $this->assertSame(
            $actor->id,
            $program->updated_by,
        );

        $this->assertSame(
            $this->referenceDate()
                ->subDays(30)
                ->toDateString(),
            $program->starts_at?->toDateString(),
        );
        $this->assertSame(
            $this->referenceDate()
                ->subDays(31)
                ->setTime(9, 0)
                ->toDateTimeString(),
            $program->published_at?->toDateTimeString(),
        );

        $this->assertSame(
            $program->id,
            $contest->program_id,
        );
        $this->assertSame(
            $profile->id,
            $contest->regulatory_profile_id,
        );
        $this->assertSame(
            AffordableRentLegalRegime::PaaLegacy2019,
            $contest->legal_regime,
        );
        $this->assertSame(
            ContestStatus::Published,
            $contest->status,
        );
        $this->assertSame(
            $actor->id,
            $contest->created_by,
        );
        $this->assertSame(
            $actor->id,
            $contest->updated_by,
        );

        $this->assertSame(
            $this->referenceDate()
                ->subDays(8)
                ->setTime(9, 0)
                ->toDateTimeString(),
            $contest->published_at?->toDateTimeString(),
        );
        $this->assertSame(
            $this->referenceDate()
                ->subDays(7)
                ->setTime(9, 0)
                ->toDateTimeString(),
            $contest->opens_at?->toDateTimeString(),
        );
        $this->assertSame(
            $this->referenceDate()
                ->addDays(90)
                ->setTime(17, 0)
                ->toDateTimeString(),
            $contest->closes_at?->toDateTimeString(),
        );

        $this->assertTrue(
            $contest->opens_at?->lte($this->referenceDate())
                ?? false,
        );
        $this->assertTrue(
            $contest->closes_at?->gte($this->referenceDate())
                ?? false,
        );
    }

    public function test_seeder_creates_exactly_three_compatible_t2_housing_units(): void
    {
        $this->seedDemo();

        $municipality = $this->demoMunicipality();
        $program = $this->demoProgram();
        $contest = $this->demoContest();

        $expectedRents = [
            'ALC-DEMO-APP-T2-01' => '390.00',
            'ALC-DEMO-APP-T2-02' => '400.00',
            'ALC-DEMO-APP-T2-03' => '410.00',
        ];

        $units = HousingUnit::query()
            ->where('municipality_id', $municipality->id)
            ->orderBy('code')
            ->get();

        $this->assertCount(3, $units);
        $this->assertSame(
            array_keys($expectedRents),
            $units->pluck('code')->all(),
        );

        foreach ($units as $unit) {
            $this->assertSame('T2', $unit->typology);
            $this->assertSame(2, $unit->bedrooms);
            $this->assertSame(
                $expectedRents[$unit->code],
                $unit->monthly_rent,
            );
            $this->assertSame(
                HousingUnitStatus::Available,
                $unit->status,
            );
            $this->assertSame(
                HousingPublicStatus::Available,
                $unit->public_status,
            );
            $this->assertSame(
                PublicVisibilityStatus::Published,
                $unit->public_visibility_status,
            );
            $this->assertSame(
                HousingLocationPrecision::Approximate,
                $unit->public_location_precision,
            );
            $this->assertTrue($unit->is_public);
            $this->assertFalse(
                $unit->public_address_visible,
            );
            $this->assertNotNull($unit->published_at);
        }

        $contestUnits = ContestHousingUnit::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->with('housingUnit')
            ->orderBy('monthly_rent')
            ->get();

        $this->assertCount(3, $contestUnits);

        foreach ($contestUnits as $contestUnit) {
            $this->assertSame(
                ContestHousingUnitStatus::Available,
                $contestUnit->status,
            );
            $this->assertSame('T2', $contestUnit->typology);
            $this->assertSame(2, $contestUnit->bedrooms);
            $this->assertSame(2, $contestUnit->min_occupants);
            $this->assertSame(4, $contestUnit->max_occupants);
            $this->assertFalse($contestUnit->accessible);
            $this->assertSame(
                $contest->opens_at?->toDateTimeString(),
                $contestUnit->availability_starts_at
                    ?->toDateTimeString(),
            );
            $this->assertSame(
                $contest->closes_at?->toDateTimeString(),
                $contestUnit->availability_ends_at
                    ?->toDateTimeString(),
            );
        }

        $typologyRule = TypologyAdequacyRule::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->sole();

        $this->assertSame(
            $this->demoProfile()->id,
            $typologyRule->regulatory_profile_id,
        );
        $this->assertSame('T2', $typologyRule->typology);
        $this->assertSame(2, $typologyRule->min_household_members);
        $this->assertSame(4, $typologyRule->max_household_members);
        $this->assertSame(2, $typologyRule->min_bedrooms);
        $this->assertSame(2, $typologyRule->max_bedrooms);
        $this->assertTrue($typologyRule->is_active);
    }

    public function test_preference_and_rent_configuration_is_demo_only_and_fail_closed(): void
    {
        $this->seedDemo();

        $program = $this->demoProgram();
        $contest = $this->demoContest();
        $profile = $this->demoProfile();

        $allocation = AllocationRuleSet::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->sole();

        $this->assertSame(
            $profile->id,
            $allocation->regulatory_profile_id,
        );
        $this->assertSame(
            AllocationRuleSetStatus::Active,
            $allocation->status,
        );
        $this->assertSame(
            AllocationMethod::RankingThenLottery,
            $allocation->allocation_method,
        );
        $this->assertTrue($allocation->allow_preferences);
        $this->assertSame(1, $allocation->minimum_preferences);
        $this->assertSame(3, $allocation->maximum_preferences);
        $this->assertTrue(
            $allocation->preferences_required_before_submission,
        );
        $this->assertFalse(
            $allocation->allow_unselected_unit_fallback,
        );
        $this->assertFalse($allocation->allow_manual_override);

        $rentRuleSet = RentRuleSet::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->sole();

        $this->assertSame(
            $profile->id,
            $rentRuleSet->regulatory_profile_id,
        );
        $this->assertSame(
            RentRuleSetStatus::Active,
            $rentRuleSet->status,
        );
        $this->assertSame(
            RentCalculationMethod::EffortRate,
            $rentRuleSet->calculation_method,
        );
        $this->assertSame(
            '35.00',
            $rentRuleSet->effort_rate_percentage,
        );
        $this->assertSame('390.00', $rentRuleSet->minimum_rent);
        $this->assertSame('410.00', $rentRuleSet->maximum_rent);
        $this->assertTrue(
            $rentRuleSet->requires_manual_approval,
        );

        $manifest = RentLimitTableManifest::query()
            ->where('rent_rule_set_id', $rentRuleSet->id)
            ->sole();

        $this->assertSame(
            $profile->id,
            $manifest->regulatory_profile_id,
        );
        $this->assertSame(
            RentLimitConfigurationStatus::Configured,
            $manifest->validation_status,
        );
        $this->assertTrue($manifest->demo_only);
        $this->assertSame(
            [
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            ],
            $manifest->municipality_coverage,
        );
        $this->assertSame(['T2'], $manifest->typology_coverage);
        $this->assertSame(1, $manifest->row_count);
        $this->assertNotNull($manifest->checksum);
        $this->assertNotSame('', trim((string) $manifest->checksum));

        $row = RentLimitTableRow::query()
            ->where('manifest_id', $manifest->id)
            ->sole();

        $this->assertSame(
            MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            $row->municipality_code,
        );
        $this->assertSame('T2', $row->typology);
        $this->assertSame('390.00', $row->minimum_rent);
        $this->assertSame('410.00', $row->maximum_rent);
        $this->assertStringStartsWith(
            'DEMO-',
            (string) $row->source_row_reference,
        );
    }

    public function test_seeder_creates_the_minimum_document_and_eligibility_catalogue(): void
    {
        $this->seedDemo();

        $program = $this->demoProgram();
        $contest = $this->demoContest();
        $profile = $this->demoProfile();

        $expectedDocumentCodes = [
            'alcanena_demo_identificacao_residencia',
            'alcanena_demo_nif',
            'alcanena_demo_nota_liquidacao_irs',
            'alcanena_demo_situacao_regular_at',
            'alcanena_demo_situacao_regular_iss',
            'recibos_vencimento',
        ];

        $requiredDocuments = RequiredDocument::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->with('documentType')
            ->get();

        $this->assertCount(6, $requiredDocuments);
        $this->assertEqualsCanonicalizing(
            $expectedDocumentCodes,
            $requiredDocuments
                ->pluck('documentType.code')
                ->all(),
        );

        foreach ($expectedDocumentCodes as $code) {
            $this->assertSame(
                1,
                DocumentType::query()
                    ->where('code', $code)
                    ->count(),
                $code,
            );
        }

        $payslipType = DocumentType::query()
            ->where('code', 'recibos_vencimento')
            ->sole();

        $payslipRequirement = RequiredDocument::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->where('document_type_id', $payslipType->id)
            ->sole();

        $this->assertTrue($payslipRequirement->is_required);
        $this->assertTrue($payslipRequirement->is_active);
        $this->assertSame(
            3,
            $payslipRequirement->required_submissions,
        );
        $this->assertSame(
            DocumentReferencePeriodUnit::Month,
            $payslipRequirement->reference_period_unit,
        );
        $this->assertTrue(
            $payslipRequirement
                ->requires_distinct_reference_periods,
        );
        $this->assertSame(
            3,
            $payslipRequirement->reference_period_recency,
        );

        $eligibility = EligibilityRuleSet::query()
            ->where('program_id', $program->id)
            ->where('contest_id', $contest->id)
            ->sole();

        $this->assertSame(
            $profile->id,
            $eligibility->regulatory_profile_id,
        );
        $this->assertSame(
            EligibilityRuleSetStatus::Active,
            $eligibility->status,
        );

        $expectedCriteria = [
            'registration_is_registered',
            'candidate_is_adult',
            'all_household_members_have_valid_residency',
            'has_household',
            'has_applicant_member',
            'has_income_information',
            'has_current_housing_situation',
            'has_required_documents_submitted',
            'contest_is_open',
            'no_duplicate_active_application',
            'typology_is_adequate',
            'rent_effort_within_35_percent',
        ];

        $this->assertEqualsCanonicalizing(
            $expectedCriteria,
            $eligibility->criteria()
                ->pluck('code')
                ->all(),
        );
    }

    public function test_regulatory_snapshots_include_the_final_catalogue_configuration(): void
    {
        $this->seedDemo();

        $program = $this->demoProgram();
        $contest = $this->demoContest();
        $profile = $this->demoProfile();
        $actor = $this->demoAnalyst();

        $programSnapshot = RegulatorySnapshot::query()
            ->where('source_type', $program->getMorphClass())
            ->where('source_id', $program->id)
            ->where(
                'context',
                RegulatoryContext::ProgramPublication->value,
            )
            ->sole();

        $contestSnapshot = RegulatorySnapshot::query()
            ->where('source_type', $contest->getMorphClass())
            ->where('source_id', $contest->id)
            ->where(
                'context',
                RegulatoryContext::ContestPublication->value,
            )
            ->sole();

        foreach ([$programSnapshot, $contestSnapshot] as $snapshot) {
            $this->assertSame(
                $this->demoMunicipality()->id,
                $snapshot->municipality_id,
            );
            $this->assertSame(
                $profile->id,
                $snapshot->regulatory_profile_id,
            );
            $this->assertSame(
                AffordableRentLegalRegime::PaaLegacy2019,
                $snapshot->legal_regime,
            );
            $this->assertSame(
                MunicipalApplicationDemoCatalogSeeder::SNAPSHOT_ORIGIN,
                $snapshot->origin,
            );
            $this->assertSame(
                $actor->id,
                $snapshot->created_by,
            );
            $this->assertNotNull($snapshot->locked_at);
            $this->assertNotSame(
                '',
                trim((string) $snapshot->checksum),
            );
        }

        $this->assertSame(
            $programSnapshot->id,
            $program->regulatory_snapshot_id,
        );
        $this->assertSame(
            $contestSnapshot->id,
            $contest->regulatory_snapshot_id,
        );

        $contestRuleSets = $contestSnapshot->rule_sets;

        $this->assertSame(
            EligibilityRuleSet::query()
                ->where('contest_id', $contest->id)
                ->sole()
                ->id,
            data_get(
                $contestRuleSets,
                'eligibility_rule_set_id',
            ),
        );
        $this->assertSame(
            RentRuleSet::query()
                ->where('contest_id', $contest->id)
                ->sole()
                ->id,
            data_get(
                $contestRuleSets,
                'rent_rule_set_id',
            ),
        );
        $this->assertSame(
            TypologyAdequacyRule::query()
                ->where('contest_id', $contest->id)
                ->sole()
                ->id,
            data_get(
                $contestRuleSets,
                'typology_rule_id',
            ),
        );
        $this->assertSame(
            AllocationRuleSet::query()
                ->where('contest_id', $contest->id)
                ->sole()
                ->id,
            data_get(
                $contestRuleSets,
                'allocation_rule_set_id',
            ),
        );

        $this->assertSame(
            'configured',
            data_get($contestSnapshot->limits, 'status'),
        );
        $this->assertSame(
            '390.00',
            data_get(
                $contestSnapshot->limits,
                'minimum_rent',
            ),
        );
        $this->assertSame(
            '410.00',
            data_get(
                $contestSnapshot->limits,
                'maximum_rent',
            ),
        );
    }

    public function test_catalog_seeder_is_idempotent_and_does_not_seed_out_of_scope_domains(): void
    {
        $this->seedDemo();

        $firstIds = [
            'profile' => $this->demoProfile()->id,
            'program' => $this->demoProgram()->id,
            'contest' => $this->demoContest()->id,
            'units' => HousingUnit::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->orderBy('code')
                ->pluck('id', 'code')
                ->all(),
            'contest_units' => ContestHousingUnit::query()
                ->where(
                    'contest_id',
                    $this->demoContest()->id,
                )
                ->orderBy('housing_unit_id')
                ->pluck('id', 'housing_unit_id')
                ->all(),
            'snapshots' => RegulatorySnapshot::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->orderBy('context')
                ->pluck('checksum', 'id')
                ->all(),
        ];

        $this->seedDemo();

        $this->assertSame(
            $firstIds['profile'],
            $this->demoProfile()->id,
        );
        $this->assertSame(
            $firstIds['program'],
            $this->demoProgram()->id,
        );
        $this->assertSame(
            $firstIds['contest'],
            $this->demoContest()->id,
        );
        $this->assertSame(
            $firstIds['units'],
            HousingUnit::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->orderBy('code')
                ->pluck('id', 'code')
                ->all(),
        );
        $this->assertSame(
            $firstIds['contest_units'],
            ContestHousingUnit::query()
                ->where(
                    'contest_id',
                    $this->demoContest()->id,
                )
                ->orderBy('housing_unit_id')
                ->pluck('id', 'housing_unit_id')
                ->all(),
        );
        $this->assertSame(
            $firstIds['snapshots'],
            RegulatorySnapshot::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->orderBy('context')
                ->pluck('checksum', 'id')
                ->all(),
        );

        $this->assertSame(
            1,
            AffordableRentRegulatoryProfile::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->count(),
        );
        $this->assertSame(
            1,
            Program::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->count(),
        );
        $this->assertSame(
            1,
            Contest::query()
                ->where('program_id', $this->demoProgram()->id)
                ->count(),
        );
        $this->assertSame(
            3,
            HousingUnit::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->count(),
        );
        $this->assertSame(
            2,
            RegulatorySnapshot::query()
                ->where(
                    'municipality_id',
                    $this->demoMunicipality()->id,
                )
                ->count(),
        );

        $this->assertDatabaseCount('contest_jury_members', 0);
        $this->assertDatabaseCount('scoring_rule_sets', 0);
        $this->assertDatabaseCount('allocation_runs', 0);
        $this->assertDatabaseCount('allocations', 0);
        $this->assertDatabaseCount('contract_templates', 0);
        $this->assertDatabaseCount('notification_templates', 0);
    }

    private function seedDemo(): void
    {
        $this->seed(
            MunicipalApplicationDemoAccessSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoCatalogSeeder::class,
        );
    }

    private function referenceDate(): CarbonImmutable
    {
        return CarbonImmutable::create(
            2026,
            7,
            27,
            0,
            0,
            0,
            'Europe/Lisbon',
        );
    }

    private function demoMunicipality(): Municipality
    {
        return Municipality::query()
            ->where(
                'code',
                MunicipalApplicationDemoAccessSeeder::MUNICIPALITY_CODE,
            )
            ->sole();
    }

    private function demoAnalyst(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL,
            )
            ->sole();
    }

    private function demoProfile(): AffordableRentRegulatoryProfile
    {
        return AffordableRentRegulatoryProfile::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::PROFILE_CODE,
            )
            ->sole();
    }

    private function demoProgram(): Program
    {
        return Program::query()
            ->where(
                'slug',
                MunicipalApplicationDemoCatalogSeeder::PROGRAM_SLUG,
            )
            ->sole();
    }

    private function demoContest(): Contest
    {
        return Contest::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
            )
            ->sole();
    }
}
