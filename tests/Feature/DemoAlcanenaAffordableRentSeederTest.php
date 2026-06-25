<?php

namespace Tests\Feature;

use App\Enums\ContestStatus;
use App\Enums\ContractTemplateStatus;
use App\Enums\EligibilityRuleSetStatus;
use App\Enums\ProgramStatus;
use App\Enums\RentRuleSetStatus;
use App\Enums\ScoringRuleSetStatus;
use App\Enums\TemplateStatus;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeWorkflowConfig;
use App\Models\AllocationRuleSet;
use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\EligibilityRuleSet;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HousingPreference;
use App\Models\HousingUnit;
use App\Models\IncomeRecord;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Models\RequiredDocument;
use App\Models\ScoringRule;
use App\Models\ScoringRuleSet;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Eligibility\EligibilityDataProvider;
use App\Services\Scoring\ScoringCriterionEvaluator;
use App\Services\Scoring\ScoringDataProvider;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAlcanenaAffordableRentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_complete_alcanena_demo_configuration_idempotently(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $program = Program::query()
            ->where('slug', DemoAlcanenaAffordableRentSeeder::PROGRAM_SLUG)
            ->firstOrFail();
        $contest = Contest::query()
            ->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)
            ->firstOrFail();

        $this->assertSame(ProgramStatus::Published, $program->status);
        $this->assertSame(ContestStatus::Published, $contest->status);
        $this->assertNotNull($program->published_at);
        $this->assertNotNull($contest->published_at);
        $this->assertSame('Município de Alcanena', $program->municipality->name);
        $this->assertSame(10, $program->rules()->count());
        $this->assertSame(5, $contest->deadlines()->count());
        $this->assertSame(3, $contest->juryMembers()->count());
        $this->assertSame(3, User::query()
            ->where('email', 'like', 'jurado.%@example.test')
            ->whereHas('roles', fn ($query) => $query->where('name', 'jury'))
            ->count());
        $this->assertDemoAccessUser('admin-demo@exemplo.pt', 'administrator');
        $this->assertDemoAccessUser('tecnico-demo@exemplo.pt', 'municipal_technician');
        $this->assertDemoAccessUser('juri-demo@exemplo.pt', 'jury');
        $this->assertDemoAccessUser('candidato-demo@exemplo.pt', 'candidate');

        $eligibility = EligibilityRuleSet::query()
            ->where('contest_id', $contest->id)
            ->firstOrFail();
        $this->assertSame(EligibilityRuleSetStatus::Active, $eligibility->status);
        $this->assertSame(22, $eligibility->criteria()->count());
        $this->assertSame(7, $eligibility->criteria()->where('requires_manual_review', true)->count());
        $this->assertDatabaseHas('eligibility_criteria', [
            'eligibility_rule_set_id' => $eligibility->id,
            'code' => 'rent_effort_within_35_percent',
            'is_mandatory' => true,
        ]);

        $scoring = ScoringRuleSet::query()
            ->where('contest_id', $contest->id)
            ->firstOrFail();
        $this->assertSame(ScoringRuleSetStatus::Active, $scoring->status);
        $this->assertSame(4, $scoring->criteria()->count());
        $this->assertSame(18, ScoringRule::query()
            ->whereHas('criterion', fn ($query) => $query->where('scoring_rule_set_id', $scoring->id))
            ->count());
        $this->assertSame(4, $scoring->tieBreakerRules()->count());

        $this->assertSame(11, DocumentType::query()->where('code', 'like', 'alcanena_%')->count());
        $this->assertSame(11, RequiredDocument::query()->where('contest_id', $contest->id)->count());
        $this->assertSame(4, ContestHousingUnit::query()->where('contest_id', $contest->id)->count());
        $this->assertSame(4, TypologyAdequacyRule::query()->where('contest_id', $contest->id)->count());
        $this->assertSame(1, AllocationRuleSet::query()->where('contest_id', $contest->id)->count());
        $this->assertSame(4, HousingUnit::query()->where('code', 'like', 'ALC-DEMO-%')->count());
        $this->assertDatabaseHas('housing_units', [
            'code' => 'ALC-DEMO-T2-MONSANTO',
            'public_reference' => 'ALC-DEMO-T2-MONSANTO',
            'public_slug' => 't2-monsanto-demo',
            'is_public' => true,
        ]);

        $this->assertSame(1, AdministrativeWorkflowConfig::query()->where('contest_id', $contest->id)->count());

        $rentRuleSet = RentRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame(RentRuleSetStatus::Active, $rentRuleSet->status);
        $this->assertSame('35.00', $rentRuleSet->effort_rate_percentage);
        $this->assertSame(4, $rentRuleSet->rules()->count());

        $contractTemplate = ContractTemplate::query()->where('contest_id', $contest->id)->firstOrFail();
        $this->assertSame(ContractTemplateStatus::Active, $contractTemplate->status);
        $this->assertSame(5, ContractClause::query()->where('contest_id', $contest->id)->count());
        $this->assertSame(5, $contractTemplate->templateClauses()->count());

        $notificationTemplates = NotificationTemplate::query()
            ->where('contest_id', $contest->id)
            ->where('code', 'like', 'alcanena_%')
            ->get();
        $this->assertSame(4, $notificationTemplates->count());
        $this->assertTrue($notificationTemplates->every(fn (NotificationTemplate $template) => $template->status === TemplateStatus::Active));
        $this->assertTrue($notificationTemplates->every(fn (NotificationTemplate $template) => $template->active_version_id !== null));
        $this->assertSame(4, NotificationEventRule::query()->where('contest_id', $contest->id)->count());

        $documentTemplates = DocumentTemplate::query()
            ->where('contest_id', $contest->id)
            ->where('code', 'like', 'alcanena_%')
            ->get();
        $this->assertSame(4, $documentTemplates->count());
        $this->assertTrue($documentTemplates->every(fn (DocumentTemplate $template) => $template->status === TemplateStatus::Active));
        $this->assertTrue($documentTemplates->every(fn (DocumentTemplate $template) => $template->active_version_id !== null));
    }

    private function assertDemoAccessUser(string $email, string $role): void
    {
        $user = User::query()
            ->where('email', $email)
            ->where('status', 'active')
            ->firstOrFail();

        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertTrue($user->roles()->where('name', $role)->exists());
    }

    public function test_alcanena_values_drive_eligibility_scoring_and_conditional_documents(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()
            ->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)
            ->firstOrFail();
        $candidate = User::factory()->create();
        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create([
                'municipality' => 'Alcanena',
                'nationality' => 'Portuguesa',
            ]);
        $household = Household::factory()->candidate($registration)->create();
        $applicant = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(35),
            'nationality' => 'Portuguesa',
            'qualification_level' => 6,
        ]);
        HouseholdMember::factory()->withoutIncome()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(8),
            'nationality' => 'Portuguesa',
            'qualification_level' => null,
            'is_dependent' => true,
        ]);
        IncomeRecord::factory()->create([
            'household_member_id' => $applicant->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => 1200,
            'annual_amount' => 14400,
            'reference_year' => 2026,
        ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'current_municipality' => 'Alcanena',
        ]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
        ]);
        $unit = ContestHousingUnit::query()
            ->where('contest_id', $contest->id)
            ->where('typology', 'T2')
            ->firstOrFail();
        $preference = new HousingPreference;
        $preference->forceFill([
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'contest_id' => $contest->id,
            'contest_housing_unit_id' => $unit->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'preference_order' => 1,
        ])->save();

        $eligibility = app(EligibilityDataProvider::class)
            ->forCandidate($candidate, $contest->program, $contest, $application->fresh());
        $this->assertTrue($eligibility['values']['all_household_members_have_valid_residency']['value']);
        $this->assertTrue($eligibility['values']['annual_income_within_alcanena_limit']['value']);
        $this->assertTrue($eligibility['values']['all_non_dependent_adults_meet_rmmg']['value']);
        $this->assertTrue($eligibility['values']['typology_is_adequate']['value']);
        $this->assertTrue($eligibility['values']['rent_effort_within_35_percent']['value']);
        $this->assertSame(48632.0, $eligibility['snapshots']['income_records']['alcanena_annual_income_limit']);

        $context = app(ScoringDataProvider::class)->forApplication($application->fresh());
        $this->assertSame(3, $context['values']['qualification_classification_points']['value']);
        $this->assertSame(4, $context['values']['average_age_classification_points']['value']);
        $this->assertSame(2, $context['values']['dependents_classification_points']['value']);
        $this->assertSame(1, $context['values']['disability_classification_points']['value']);

        $ruleSet = ScoringRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();
        $total = $ruleSet->criteria()
            ->with('rules')
            ->get()
            ->sum(fn ($criterion) => app(ScoringCriterionEvaluator::class)
                ->evaluate($criterion, $context)['points_awarded']);
        $this->assertSame(3.0, (float) $total);

        $checklist = app(DocumentChecklistService::class)->forApplication($application->fresh());
        $initialCodes = collect($checklist['items'])->pluck('document_type.code');
        $this->assertNotContains('alcanena_declaracao_gravidez', $initialCodes);
        $this->assertNotContains('alcanena_atestado_incapacidade', $initialCodes);
        $this->assertNotContains('alcanena_rendimentos_dispensa_irs', $initialCodes);

        $applicant->forceFill([
            'has_multiple_disabilities' => true,
            'is_pregnant' => true,
            'is_exempt_from_irs' => true,
        ])->save();
        $conditionalCodes = collect(
            app(DocumentChecklistService::class)
                ->forApplication($application->fresh())['items'],
        )->pluck('document_type.code');

        $this->assertContains('alcanena_declaracao_gravidez', $conditionalCodes);
        $this->assertContains('alcanena_atestado_incapacidade', $conditionalCodes);
        $this->assertContains('alcanena_rendimentos_dispensa_irs', $conditionalCodes);
    }

    public function test_candidate_household_member_form_exposes_the_alcanena_classification_fields(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');
        $registration = AdhesionRegistration::factory()
            ->registered()
            ->for($candidate)
            ->create();
        Household::factory()->candidate($registration)->create();

        $this->actingAs($candidate)
            ->get(route('candidate.household-members.create'))
            ->assertOk()
            ->assertSee('Nível de qualificação')
            ->assertSee('Tem multideficiência')
            ->assertSee('Está grávida')
            ->assertSee('Dispensado de entregar IRS');
    }
}
