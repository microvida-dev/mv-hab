<?php

namespace Tests\Feature\Seeders;

use App\Enums\AdhesionRegistrationStatus;
use App\Enums\ApplicationPreferenceSource;
use App\Enums\ApplicationStatus;
use App\Enums\HouseholdRelationship;
use App\Enums\HousingCompatibilityStatus;
use App\Enums\HousingCondition;
use App\Enums\HousingStatus;
use App\Enums\IncomeSourceType;
use App\Enums\ProfessionalStatus;
use App\Enums\SimulationResultStatus;
use App\Enums\SimulationScope;
use App\Enums\SimulationSessionStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingPreference;
use App\Models\IncomeRecord;
use App\Models\IncomeSource;
use App\Models\SimulationRecommendedContest;
use App\Models\SimulationResult;
use App\Models\SimulationSession;
use App\Models\User;
use App\Services\Applications\HousingPreferenceSnapshotService;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCandidateSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MunicipalApplicationDemoCandidateSeederTest extends TestCase
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

    public function test_seeder_completes_the_candidate_adhesion_registration(): void
    {
        $this->seedDemo();

        $candidate = $this->candidate();
        $registration = $this->registration();

        $this->assertSame('João Miguel Ferreira', $candidate->name);
        $this->assertSame(
            MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            $candidate->email,
        );
        $this->assertSame(
            $this->demoMunicipalityId(),
            $candidate->municipality_id,
        );
        $this->assertFalse($candidate->mfa_required);
        $this->assertTrue($candidate->hasRole('candidate'));

        $this->assertSame($candidate->id, $registration->user_id);
        $this->assertSame(
            AdhesionRegistrationStatus::Registered,
            $registration->status,
        );
        $this->assertSame('João Miguel Ferreira', $registration->full_name);
        $this->assertSame($candidate->email, $registration->email);
        $this->assertSame(
            MunicipalApplicationDemoCandidateSeeder::CANDIDATE_NIF,
            $registration->nif,
        );
        $this->assertSame(
            '1990-03-15',
            $registration->birth_date?->toDateString(),
        );
        $this->assertSame('Portuguesa', $registration->nationality);
        $this->assertSame(
            'Rua das Oliveiras, n.º 25',
            $registration->address,
        );
        $this->assertSame('2380-000', $registration->postal_code);
        $this->assertSame('Alcanena', $registration->city);
        $this->assertSame('Alcanena', $registration->municipality);
        $this->assertTrue($registration->accepts_terms);
        $this->assertTrue($registration->accepts_data_processing);
        $this->assertNotNull($registration->accepted_terms_at);
        $this->assertNotNull(
            $registration->accepted_data_processing_at,
        );
        $this->assertNotNull($registration->submitted_at);
        $this->assertSame([], $registration->missingRequiredFields());
        $this->assertSame(100, $registration->completionPercentage());

        $histories = $registration->statusHistories()
            ->oldest('id')
            ->get();

        $this->assertCount(2, $histories);
        $this->assertNull($histories[0]->from_status);
        $this->assertSame(
            AdhesionRegistrationStatus::Incomplete,
            $histories[0]->to_status,
        );
        $this->assertSame(
            AdhesionRegistrationStatus::Incomplete,
            $histories[1]->from_status,
        );
        $this->assertSame(
            AdhesionRegistrationStatus::Registered,
            $histories[1]->to_status,
        );
        $this->assertSame(
            [$candidate->id],
            $histories->pluck('changed_by')->unique()->values()->all(),
        );
    }

    public function test_seeder_creates_the_three_person_household_and_income_records(): void
    {
        $this->seedDemo();

        $registration = $this->registration();
        $household = $this->household();

        $this->assertSame(
            $registration->id,
            $household->adhesion_registration_id,
        );
        $this->assertSame(
            $this->demoMunicipalityId(),
            $household->municipality_id,
        );
        $this->assertSame(
            'Agregado de João Miguel Ferreira',
            $household->name,
        );
        $this->assertSame('family', $household->household_type);
        $this->assertSame(3, $household->members_count);
        $this->assertSame('2170.00', $household->monthly_income);
        $this->assertSame(2170.0, $household->totalMonthlyIncome());
        $this->assertSame(30380.0, $household->totalAnnualIncome());

        $members = $household->members()
            ->orderBy('birth_date')
            ->get()
            ->keyBy('full_name');

        $this->assertCount(3, $members);

        $candidate = $members->get('João Miguel Ferreira');
        $spouse = $members->get('Ana Sofia Ferreira');
        $child = $members->get('Inês Ferreira');

        $this->assertInstanceOf(HouseholdMember::class, $candidate);
        $this->assertInstanceOf(HouseholdMember::class, $spouse);
        $this->assertInstanceOf(HouseholdMember::class, $child);

        $this->assertTrue($candidate->is_applicant);
        $this->assertSame(
            HouseholdRelationship::Applicant,
            $candidate->relationship,
        );
        $this->assertSame(
            ProfessionalStatus::Employed,
            $candidate->professional_status,
        );
        $this->assertSame(
            MunicipalApplicationDemoCandidateSeeder::CANDIDATE_NIF,
            $candidate->nif,
        );
        $this->assertFalse($candidate->is_dependent);
        $this->assertFalse($candidate->has_no_income);
        $this->assertSame('1250.00', $candidate->monthly_declared_income);
        $this->assertSame('17500.00', $candidate->annual_declared_income);

        $this->assertFalse($spouse->is_applicant);
        $this->assertSame(
            HouseholdRelationship::Spouse,
            $spouse->relationship,
        );
        $this->assertSame(
            ProfessionalStatus::Employed,
            $spouse->professional_status,
        );
        $this->assertSame(
            MunicipalApplicationDemoCandidateSeeder::SPOUSE_NIF,
            $spouse->nif,
        );
        $this->assertFalse($spouse->is_dependent);
        $this->assertFalse($spouse->has_no_income);
        $this->assertSame('920.00', $spouse->monthly_declared_income);
        $this->assertSame('12880.00', $spouse->annual_declared_income);

        $this->assertFalse($child->is_applicant);
        $this->assertSame(
            HouseholdRelationship::Child,
            $child->relationship,
        );
        $this->assertSame(
            ProfessionalStatus::Student,
            $child->professional_status,
        );
        $this->assertSame(
            MunicipalApplicationDemoCandidateSeeder::CHILD_NIF,
            $child->nif,
        );
        $this->assertTrue($child->is_dependent);
        $this->assertTrue($child->is_student);
        $this->assertTrue($child->has_no_income);
        $this->assertSame('Menor dependente', $child->no_income_reason);
        $this->assertSame('0.00', $child->monthly_declared_income);
        $this->assertSame('0.00', $child->annual_declared_income);

        $employment = IncomeSource::query()
            ->where('code', IncomeSourceType::Employment->value)
            ->sole();

        $incomeRecords = IncomeRecord::query()
            ->where('household_id', $household->id)
            ->with('householdMember')
            ->orderBy('monthly_amount', 'desc')
            ->get();

        $this->assertCount(2, $incomeRecords);
        $this->assertSame(
            [$employment->id],
            $incomeRecords
                ->pluck('income_source_id')
                ->unique()
                ->values()
                ->all(),
        );
        $this->assertSame(
            ['1250.00', '920.00'],
            $incomeRecords->pluck('monthly_amount')->all(),
        );
        $this->assertSame(
            ['17500.00', '12880.00'],
            $incomeRecords->pluck('annual_amount')->all(),
        );
        $this->assertSame(
            [2026],
            $incomeRecords
                ->pluck('reference_year')
                ->unique()
                ->values()
                ->all(),
        );
        $this->assertTrue(
            $incomeRecords->every(
                static fn (IncomeRecord $record): bool => $record->is_current
                    && $record->is_taxable,
            ),
        );
    }

    public function test_seeder_creates_the_current_housing_situation(): void
    {
        $this->seedDemo();

        $registration = $this->registration();
        $situation = $this->housingSituation();

        $this->assertSame(
            $registration->id,
            $situation->adhesion_registration_id,
        );
        $this->assertSame(HousingStatus::Rented, $situation->housing_status);
        $this->assertSame(
            'Rua do Mercado, n.º 10',
            $situation->current_address,
        );
        $this->assertSame('2380-000', $situation->current_postal_code);
        $this->assertSame('Alcanena', $situation->current_city);
        $this->assertSame('Alcanena', $situation->current_municipality);
        $this->assertTrue($situation->resides_in_municipality);
        $this->assertTrue($situation->works_in_municipality);
        $this->assertSame(
            HousingCondition::Adequate,
            $situation->current_housing_condition,
        );
        $this->assertSame('T2', $situation->current_housing_typology);
        $this->assertSame(2, $situation->current_housing_rooms);
        $this->assertSame('650.00', $situation->current_monthly_rent);
        $this->assertSame('75.00', $situation->current_housing_expense);
        $this->assertFalse($situation->is_overcrowded);
        $this->assertFalse($situation->is_at_risk_of_eviction);
        $this->assertFalse($situation->is_homeless);
        $this->assertFalse($situation->is_temporary_accommodation);
        $this->assertFalse($situation->has_accessibility_needs);
        $this->assertFalse($situation->has_high_rent_burden);
        $this->assertSame(
            30.0,
            $situation->effortRate(2170.0),
        );
    }

    public function test_seeder_runs_the_authenticated_simulator_and_recommends_the_demo_contest(): void
    {
        $this->seedDemo();

        $candidate = $this->candidate();
        $contest = $this->contest();

        $sessions = SimulationSession::query()
            ->where('user_id', $candidate->id)
            ->with([
                'inputSnapshot',
                'result',
                'recommendedContests',
            ])
            ->get();

        $this->assertCount(1, $sessions);

        $session = $sessions->sole();
        $result = $session->result;

        $this->assertSame(
            $this->demoMunicipalityId(),
            $session->municipality_id,
        );
        $this->assertSame($this->registration()->id, $session->adhesion_registration_id);
        $this->assertSame(SimulationScope::Authenticated, $session->scope);
        $this->assertSame(
            SimulationSessionStatus::Completed,
            $session->status,
        );
        $this->assertSame(
            SimulationResultStatus::LikelyEligible,
            $session->result_status,
        );
        $this->assertNotNull($session->started_at);
        $this->assertNotNull($session->completed_at);
        $this->assertNotNull($session->inputSnapshot);
        $this->assertInstanceOf(SimulationResult::class, $result);

        $this->assertSame(
            SimulationResultStatus::LikelyEligible,
            $result->result_status,
        );
        $this->assertSame('T2', $result->recommended_typology);
        $this->assertSame(2, $result->recommended_bedrooms);
        $this->assertSame('410.00', $result->estimated_rent_max);
        $this->assertSame('35.00', $result->estimated_effort_rate);
        $this->assertSame(0, $result->blocking_impediments_count);
        $this->assertGreaterThanOrEqual(
            1,
            $result->recommended_contests_count,
        );

        $recommendation = $session->recommendedContests
            ->firstWhere('contest_id', $contest->id);

        $this->assertInstanceOf(
            SimulationRecommendedContest::class,
            $recommendation,
        );
        $this->assertContains(
            'T2',
            $recommendation->recommended_typologies,
        );
        $this->assertGreaterThanOrEqual(
            80.0,
            (float) $recommendation->match_score,
        );
    }

    public function test_seeder_creates_one_unsubmitted_application_draft(): void
    {
        $this->seedDemo();

        $candidate = $this->candidate();
        $registration = $this->registration();
        $household = $this->household();
        $situation = $this->housingSituation();
        $contest = $this->contest();
        $application = $this->application();

        $this->assertSame($candidate->id, $application->user_id);
        $this->assertSame(
            $registration->id,
            $application->adhesion_registration_id,
        );
        $this->assertSame($contest->program_id, $application->program_id);
        $this->assertSame($contest->id, $application->contest_id);
        $this->assertSame($household->id, $application->household_id);
        $this->assertSame(
            $situation->id,
            $application->current_housing_situation_id,
        );
        $this->assertSame(ApplicationStatus::Draft, $application->status);
        $this->assertSame(
            MunicipalApplicationDemoCandidateSeeder::APPLICATION_NOTE,
            $application->candidate_notes,
        );
        $this->assertNotNull($application->public_id);
        $this->assertNull($application->application_number);
        $this->assertNull($application->submitted_at);
        $this->assertNull($application->locked_at);
        $this->assertFalse($application->declaration_accepted);
        $this->assertFalse($application->contest_rules_accepted);
        $this->assertFalse($application->data_processing_accepted);
        $this->assertFalse($application->truthfulness_accepted);
        $this->assertFalse($application->data_current_confirmed);
        $this->assertSame($candidate->id, $application->created_by);
        $this->assertSame($candidate->id, $application->updated_by);

        $histories = $application->statusHistories()
            ->oldest('id')
            ->get();

        $this->assertCount(1, $histories);
        $this->assertNull($histories[0]->from_status);
        $this->assertSame(ApplicationStatus::Draft, $histories[0]->to_status);
        $this->assertSame($candidate->id, $histories[0]->changed_by);

        $this->assertSame(0, $application->snapshots()->count());
        $this->assertSame(0, $application->applicationDocuments()->count());
        $this->assertSame(0, $application->declarations()->count());
        $this->assertSame(0, $application->documentSubmissions()->count());
    }

    public function test_seeder_creates_three_live_official_compatible_preferences(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $candidate = $this->candidate();

        $application->refresh();

        $this->assertSame(
            ApplicationPreferenceSource::Official,
            $application->preference_source,
        );
        $this->assertNotNull(
            $application->official_preferences_initialized_at,
        );
        $this->assertNull(
            $application->legacy_preferences_reconciled_at,
        );

        $preferences = HousingPreference::query()
            ->where('application_id', $application->id)
            ->with(['contestHousingUnit', 'housingUnit'])
            ->orderBy('preference_order')
            ->get();

        $this->assertCount(3, $preferences);
        $this->assertSame([1, 2, 3], $preferences->pluck('preference_order')->all());
        $this->assertSame(
            [$candidate->id],
            $preferences->pluck('user_id')->unique()->values()->all(),
        );
        $this->assertSame(
            [$application->contest_id],
            $preferences->pluck('contest_id')->unique()->values()->all(),
        );
        $this->assertSame(
            [
                'ALC-DEMO-APP-T2-01',
                'ALC-DEMO-APP-T2-02',
                'ALC-DEMO-APP-T2-03',
            ],
            $preferences->pluck('housingUnit.code')->all(),
        );

        foreach ($preferences as $preference) {
            $this->assertSame(
                HousingCompatibilityStatus::Compatible,
                $preference->compatibility_status,
            );
            $this->assertNotNull($preference->compatibility_snapshot);
            $this->assertNotNull($preference->evaluated_at);
            $this->assertNull($preference->invalidated_at);
            $this->assertNull($preference->submitted_at);
            $this->assertNull($preference->locked_at);
        }

        $freshApplication = $application->fresh();
        $this->assertInstanceOf(Application::class, $freshApplication);

        $snapshot = app(HousingPreferenceSnapshotService::class)
            ->forApplication($freshApplication);

        $this->assertCount(3, $snapshot);
        $this->assertSame(
            [1, 2, 3],
            collect($snapshot)->pluck('preference_order')->all(),
        );
        $this->assertSame(
            ['housing_preferences'],
            collect($snapshot)->pluck('source')->unique()->values()->all(),
        );

        $this->assertSame(0, $application->snapshots()->count());
    }

    public function test_candidate_seeder_is_idempotent_and_stays_within_draft_scope(): void
    {
        $this->seedDemo();

        $candidate = $this->candidate();
        $application = $this->application();

        $firstIds = [
            'registration' => $this->registration()->id,
            'household' => $this->household()->id,
            'members' => HouseholdMember::query()
                ->where('household_id', $this->household()->id)
                ->orderBy('full_name')
                ->pluck('id', 'full_name')
                ->all(),
            'income_records' => IncomeRecord::query()
                ->where('household_id', $this->household()->id)
                ->orderBy('id')
                ->pluck('id')
                ->all(),
            'housing' => $this->housingSituation()->id,
            'simulation' => SimulationSession::query()
                ->where('user_id', $candidate->id)
                ->sole()
                ->id,
            'application' => $application->id,
            'preferences' => HousingPreference::query()
                ->where('application_id', $application->id)
                ->orderBy('preference_order')
                ->pluck('id', 'preference_order')
                ->all(),
        ];
        $auditCount = $this->auditRecordCount();

        $this->seedDemo();

        $this->assertSame($firstIds['registration'], $this->registration()->id);
        $this->assertSame($firstIds['household'], $this->household()->id);
        $this->assertSame(
            $firstIds['members'],
            HouseholdMember::query()
                ->where('household_id', $this->household()->id)
                ->orderBy('full_name')
                ->pluck('id', 'full_name')
                ->all(),
        );
        $this->assertSame(
            $firstIds['income_records'],
            IncomeRecord::query()
                ->where('household_id', $this->household()->id)
                ->orderBy('id')
                ->pluck('id')
                ->all(),
        );
        $this->assertSame($firstIds['housing'], $this->housingSituation()->id);
        $this->assertSame(
            $firstIds['simulation'],
            SimulationSession::query()
                ->where('user_id', $candidate->id)
                ->sole()
                ->id,
        );
        $this->assertSame($firstIds['application'], $this->application()->id);
        $this->assertSame(
            $firstIds['preferences'],
            HousingPreference::query()
                ->where('application_id', $application->id)
                ->orderBy('preference_order')
                ->pluck('id', 'preference_order')
                ->all(),
        );
        $this->assertSame($auditCount, $this->auditRecordCount());

        $this->assertSame(
            1,
            AdhesionRegistration::query()
                ->where('user_id', $candidate->id)
                ->count(),
        );
        $this->assertSame(
            1,
            Household::query()
                ->where(
                    'adhesion_registration_id',
                    $this->registration()->id,
                )
                ->count(),
        );
        $this->assertSame(
            3,
            HouseholdMember::query()
                ->where('household_id', $this->household()->id)
                ->count(),
        );
        $this->assertSame(
            2,
            IncomeRecord::query()
                ->where('household_id', $this->household()->id)
                ->count(),
        );
        $this->assertSame(
            1,
            CurrentHousingSituation::query()
                ->where(
                    'adhesion_registration_id',
                    $this->registration()->id,
                )
                ->count(),
        );
        $this->assertSame(
            1,
            SimulationSession::query()
                ->where('user_id', $candidate->id)
                ->count(),
        );
        $this->assertSame(
            1,
            Application::query()
                ->where('user_id', $candidate->id)
                ->where('contest_id', $this->contest()->id)
                ->count(),
        );
        $this->assertSame(
            3,
            HousingPreference::query()
                ->where('application_id', $application->id)
                ->count(),
        );

        $this->assertDatabaseCount('application_snapshots', 0);
        $this->assertDatabaseCount('application_documents', 0);
        $this->assertDatabaseCount('application_declarations', 0);
        $this->assertDatabaseCount('document_submissions', 0);
        $this->assertDatabaseCount('eligibility_checks', 0);
        $this->assertDatabaseCount('housing_visits', 0);
        $this->assertDatabaseCount('application_reports', 0);
        $this->assertDatabaseCount('document_dossiers', 0);
    }

    private function seedDemo(): void
    {
        $this->seed(
            MunicipalApplicationDemoAccessSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoCatalogSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoCandidateSeeder::class,
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

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->sole();
    }

    private function registration(): AdhesionRegistration
    {
        return AdhesionRegistration::query()
            ->where('user_id', $this->candidate()->id)
            ->sole();
    }

    private function household(): Household
    {
        return Household::query()
            ->where(
                'adhesion_registration_id',
                $this->registration()->id,
            )
            ->sole();
    }

    private function housingSituation(): CurrentHousingSituation
    {
        return CurrentHousingSituation::query()
            ->where(
                'adhesion_registration_id',
                $this->registration()->id,
            )
            ->sole();
    }

    private function contest(): Contest
    {
        return Contest::query()
            ->where(
                'code',
                MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
            )
            ->sole();
    }

    private function application(): Application
    {
        return Application::query()
            ->where('user_id', $this->candidate()->id)
            ->where('contest_id', $this->contest()->id)
            ->sole();
    }

    private function demoMunicipalityId(): int
    {
        return (int) $this->candidate()->municipality_id;
    }

    private function auditRecordCount(): int
    {
        return (int) (
            DB::table('audit_logs')->count()
            + DB::table('audit_events')->count()
        );
    }
}
