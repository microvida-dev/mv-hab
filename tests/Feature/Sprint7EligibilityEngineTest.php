<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use App\Enums\EligibilityResult;
use App\Enums\EligibilityRuleSetStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\EligibilityCheck;
use App\Models\EligibilityCheckResult;
use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\RequiredDocument;
use App\Models\User;
use App\Services\Eligibility\EligibilityCheckService;
use App\Services\Eligibility\EligibilityRuleSetResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint7EligibilityEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_eligibility_area_requires_authentication_role_and_ownership(): void
    {
        $this->get(route('candidate.eligibility.index'))->assertRedirect(route('login'));

        $administrator = $this->userWithRole('administrator');
        $this->actingAs($administrator)
            ->get(route('candidate.eligibility.index'))
            ->assertForbidden();

        [$candidate, , , , , $contest] = $this->completeContext();
        $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('registration_is_registered'),
        ]);

        $this->actingAs($candidate)
            ->get(route('candidate.eligibility.index'))
            ->assertOk()
            ->assertSee('Esta verificação é indicativa');

        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $otherCandidate = $this->userWithRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.eligibility.show', $check))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_backoffice_or_see_technical_messages(): void
    {
        [$candidate, , , , , $contest] = $this->completeContext();
        $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('registration_is_registered'),
        ]);
        $this->actingAs($candidate);
        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);

        $this->get(route('backoffice.eligibility.rule-sets.index'))->assertForbidden();
        $this->get(route('backoffice.eligibility.checks.index'))->assertForbidden();
        $this->get(route('candidate.eligibility.show', $check))
            ->assertOk()
            ->assertSee('Condição cumprida')
            ->assertDontSee('valor_atual')
            ->assertDontSee('Mensagem técnica');
    }

    public function test_backoffice_eligibility_detail_presents_human_readable_conditions_without_raw_codes(): void
    {
        [$candidate, $registration, , , , $contest] = $this->completeContext();
        $technician = $this->userWithRole('municipal_technician');
        $ruleSet = $this->activeRuleSet($contest->program, $contest);
        $check = EligibilityCheck::factory()->create([
            'eligibility_rule_set_id' => $ruleSet->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'adhesion_registration_id' => $registration->id,
            'user_id' => $candidate->id,
            'result' => EligibilityResult::Ineligible->value,
            'summary' => 'Existem condições mínimas por cumprir.',
            'missing_data' => ['typology_is_adequate', 'rent_effort_within_35_percent'],
        ]);

        EligibilityCheckResult::factory()->create([
            'eligibility_check_id' => $check->id,
            'eligibility_criterion_id' => null,
            'code' => 'all_non_dependent_adults_meet_rmmg',
            'name' => 'Rendimento mínimo dos adultos não dependentes',
            'category' => EligibilityCriterionCategory::Income->value,
            'result' => EligibilityCriterionResult::Failed->value,
            'operator' => EligibilityOperator::IsTrue->value,
            'actual_value' => ['value' => false],
            'expected_value' => [
                'currency' => 'EUR',
                'reference_year' => 2026,
                'monthly_minimum' => 920,
            ],
            'message' => 'O requisito de acesso não se encontra cumprido.',
            'technical_message' => 'Critério all_non_dependent_adults_meet_rmmg avaliado com operador is_true: resultado=failed; valor_atual=false; valor_esperado={"monthly_minimum":920}.',
        ]);

        EligibilityCheckResult::factory()->create([
            'eligibility_check_id' => $check->id,
            'eligibility_criterion_id' => null,
            'code' => 'typology_is_adequate',
            'name' => 'Composição adequada às tipologias escolhidas',
            'category' => EligibilityCriterionCategory::Typology->value,
            'result' => EligibilityCriterionResult::InsufficientData->value,
            'operator' => EligibilityOperator::IsTrue->value,
            'actual_value' => null,
            'expected_value' => [],
            'message' => 'Não existem dados suficientes para avaliar esta condição.',
            'technical_message' => 'Critério typology_is_adequate avaliado com operador is_true.',
        ]);

        EligibilityCheckResult::factory()->create([
            'eligibility_check_id' => $check->id,
            'eligibility_criterion_id' => null,
            'code' => 'rent_effort_within_35_percent',
            'name' => 'Taxa de esforço máxima de 35%',
            'category' => EligibilityCriterionCategory::Income->value,
            'result' => EligibilityCriterionResult::InsufficientData->value,
            'operator' => EligibilityOperator::IsTrue->value,
            'actual_value' => null,
            'expected_value' => ['maximum_percentage' => 35],
            'message' => 'Não existem dados suficientes para avaliar esta condição.',
            'technical_message' => 'Critério rent_effort_within_35_percent avaliado com operador is_true; valor_esperado={"maximum_percentage":35}.',
        ]);

        $this->actingAs($technician)
            ->get(route('backoffice.eligibility.checks.show', $check))
            ->assertOk()
            ->assertSee('Dados a completar')
            ->assertSee('Composição adequada às tipologias escolhidas')
            ->assertSee('Taxa de esforço máxima de 35%')
            ->assertSee('Cada adulto não dependente do agregado deve comprovar rendimento mensal mínimo de 920,00 €')
            ->assertSee('35%')
            ->assertDontSee('all_non_dependent_adults_meet_rmmg')
            ->assertDontSee('typology_is_adequate')
            ->assertDontSee('rent_effort_within_35_percent')
            ->assertDontSee('valor_esperado')
            ->assertDontSee('monthly_minimum');
    }

    public function test_admin_can_create_update_activate_and_archive_rule_set_with_audit(): void
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->create();

        $this->actingAs($administrator)
            ->post(route('backoffice.eligibility.rule-sets.store'), [
                'program_id' => $program->id,
                'name' => 'Regra administrativa de teste',
                'status' => EligibilityRuleSetStatus::Draft->value,
                'is_default' => '1',
            ])
            ->assertRedirect();

        $ruleSet = EligibilityRuleSet::query()->firstOrFail();
        $this->assertSame(EligibilityRuleSetStatus::Draft, $ruleSet->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'eligibility',
            'action' => 'rule_set_create',
        ]);

        $this->actingAs($administrator)
            ->put(route('backoffice.eligibility.rule-sets.update', $ruleSet), [
                'program_id' => $program->id,
                'name' => 'Regra administrativa atualizada',
                'status' => EligibilityRuleSetStatus::Draft->value,
            ])
            ->assertRedirect();

        $this->actingAs($administrator)
            ->post(route('backoffice.eligibility.rule-sets.activate', $ruleSet))
            ->assertRedirect();
        $this->assertSame(EligibilityRuleSetStatus::Active, $ruleSet->fresh()->status);

        $this->actingAs($administrator)
            ->post(route('backoffice.eligibility.rule-sets.archive', $ruleSet))
            ->assertRedirect();
        $this->assertSame(EligibilityRuleSetStatus::Archived, $ruleSet->fresh()->status);
    }

    public function test_rule_set_requires_context_and_candidate_cannot_create_configuration(): void
    {
        $administrator = $this->userWithRole('administrator');
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($administrator)
            ->post(route('backoffice.eligibility.rule-sets.store'), [
                'name' => 'Sem contexto',
                'status' => EligibilityRuleSetStatus::Draft->value,
            ])
            ->assertSessionHasErrors(['program_id', 'contest_id']);

        $program = Program::factory()->create();
        $this->actingAs($candidate)
            ->post(route('backoffice.eligibility.rule-sets.store'), [
                'program_id' => $program->id,
                'name' => 'Tentativa indevida',
                'status' => EligibilityRuleSetStatus::Draft->value,
            ])
            ->assertForbidden();
    }

    public function test_contest_rule_set_precedes_program_and_draft_or_archived_sets_are_ignored(): void
    {
        $program = Program::factory()->create();
        $contest = Contest::factory()->for($program)->create();
        $programSet = $this->activeRuleSet($program);
        $contestSet = $this->activeRuleSet($program, $contest);

        $resolver = app(EligibilityRuleSetResolver::class);
        $this->assertTrue($resolver->resolve($program, $contest)->is($contestSet));

        $contestSet->forceFill(['status' => EligibilityRuleSetStatus::Draft])->save();
        $this->assertTrue($resolver->resolve($program, $contest)->is($programSet));

        $programSet->forceFill(['status' => EligibilityRuleSetStatus::Archived])->save();
        $this->assertNull($resolver->resolve($program, $contest));
    }

    public function test_engine_creates_results_snapshots_and_eligible_result(): void
    {
        [$candidate, , , , , $contest] = $this->completeContext();
        $ruleSet = $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('registration_is_registered'),
            $this->criterion('candidate_is_adult'),
            $this->criterion('contest_is_open'),
            $this->criterion('has_household'),
            $this->criterion('has_applicant_member'),
            $this->criterion('has_income_information'),
            $this->criterion('has_current_housing_situation'),
        ]);

        $this->actingAs($candidate);
        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);

        $this->assertSame(EligibilityResult::Eligible, $check->result);
        $this->assertSame($ruleSet->criteria()->count(), $check->results()->count());
        $this->assertSame(8, $check->snapshots()->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $candidate->id,
            'module' => 'eligibility',
            'action' => 'run_pre_check',
        ]);
        $snapshotJson = json_encode($check->snapshots()->pluck('data'));
        $this->assertStringNotContainsString('storage_path', $snapshotJson);
        $this->assertStringNotContainsString('document_number', $snapshotJson);
        $this->assertStringNotContainsString('nif', $snapshotJson);
    }

    public function test_mandatory_failure_is_ineligible_and_inactive_criterion_is_not_evaluated(): void
    {
        [$candidate, $registration, , , , $contest] = $this->completeContext();
        $registration->forceFill(['status' => 'incomplete'])->save();
        $ruleSet = $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('registration_is_registered'),
            [
                ...$this->criterion('candidate_is_adult'),
                'is_active' => false,
            ],
        ]);

        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);

        $this->assertSame(EligibilityResult::Ineligible, $check->result);
        $this->assertSame(1, $check->results()->count());
        $this->assertDatabaseHas('eligibility_check_results', [
            'eligibility_check_id' => $check->id,
            'code' => 'registration_is_registered',
            'result' => EligibilityCriterionResult::Failed->value,
        ]);
        $this->assertSame(2, $ruleSet->criteria()->count());
    }

    public function test_missing_income_returns_insufficient_data_and_manual_criterion_requires_review(): void
    {
        [$candidate, , , $member, , $contest] = $this->completeContext();
        $member->incomeRecords()->delete();
        $member->update(['has_no_income' => false]);
        $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('has_income_information'),
        ]);

        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::InsufficientData, $check->result);
        $this->assertSame(['has_income_information'], $check->missing_data);

        $contest->eligibilityRuleSets()->delete();
        $this->activeRuleSet($contest->program, $contest, [[
            ...$this->criterion('no_declared_property_impediment'),
            'requires_manual_review' => true,
        ]]);
        $reviewCheck = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::RequiresReview, $reviewCheck->result);
    }

    public function test_candidate_without_registration_receives_insufficient_data_instead_of_ineligible(): void
    {
        $candidate = $this->userWithRole('candidate');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $this->activeRuleSet($program, $contest, [
            $this->criterion('registration_is_registered'),
            $this->criterion('candidate_is_adult'),
            $this->criterion('has_household'),
            $this->criterion('has_current_housing_situation'),
        ]);

        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);

        $this->assertSame(EligibilityResult::InsufficientData, $check->result);
    }

    public function test_income_minimum_and_maximum_operators_are_evaluated(): void
    {
        [$candidate, , , , , $contest] = $this->completeContext();
        $this->activeRuleSet($contest->program, $contest, [
            [
                ...$this->criterion('income_above_minimum'),
                'operator' => EligibilityOperator::GreaterThanOrEqual->value,
                'minimum_value' => 10000,
            ],
            [
                ...$this->criterion('income_below_maximum'),
                'operator' => EligibilityOperator::LessThanOrEqual->value,
                'maximum_value' => 20000,
            ],
        ]);

        $check = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Eligible, $check->result);

        EligibilityCriterion::query()
            ->where('code', 'income_below_maximum')
            ->update(['maximum_value' => 10000]);
        $failedCheck = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Ineligible, $failedCheck->result);
    }

    public function test_document_submission_and_validation_criteria_use_document_checklist_states(): void
    {
        [$candidate, $registration, , , , $contest] = $this->completeContext();
        $documentType = DocumentType::factory()->create();
        $required = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'required_for' => DocumentAppliesTo::AdhesionRegistration->value,
        ]);
        $this->activeRuleSet($contest->program, $contest, [
            [
                ...$this->criterion('has_required_documents_submitted'),
                'operator' => EligibilityOperator::AllRequiredDocumentsSubmitted->value,
            ],
        ]);

        $missing = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Ineligible, $missing->result);

        $submission = DocumentSubmission::factory()->create([
            'document_type_id' => $documentType->id,
            'required_document_id' => $required->id,
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'status' => DocumentStatus::Submitted->value,
        ]);
        $submitted = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Eligible, $submitted->result);

        EligibilityCriterion::query()->update([
            'code' => 'has_required_documents_validated',
            'operator' => EligibilityOperator::AllRequiredDocumentsValidated->value,
        ]);
        $notValidated = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Ineligible, $notValidated->result);

        $submission->forceFill(['status' => DocumentStatus::Validated])->save();
        $validated = app(EligibilityCheckService::class)->candidatePreCheck($candidate, contest: $contest);
        $this->assertSame(EligibilityResult::Eligible, $validated->result);
    }

    public function test_admin_can_create_unique_criterion_and_updates_are_audited(): void
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->create();
        $ruleSet = $this->activeRuleSet($program);
        $payload = [
            'code' => 'registration_is_registered_manual_review_test',
            'name' => 'Registo finalizado',
            'category' => EligibilityCriterionCategory::Identity->value,
            'target' => 'adhesion_registration',
            'operator' => EligibilityOperator::IsTrue->value,
            'is_mandatory' => '1',
            'is_active' => '1',
        ];

        $this->actingAs($administrator)
            ->post(route('backoffice.eligibility.criteria.store', $ruleSet), $payload)
            ->assertRedirect();
        $criterion = EligibilityCriterion::query()->firstOrFail();

        $this->from(route('backoffice.eligibility.criteria.create', $ruleSet))
            ->actingAs($administrator)
            ->post(route('backoffice.eligibility.criteria.store', $ruleSet), $payload)
            ->assertSessionHasErrors('code');

        $this->actingAs($administrator)
            ->put(route('backoffice.eligibility.criteria.update', $criterion), [
                ...$payload,
                'name' => 'Registo de Adesão finalizado',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'eligibility',
            'action' => 'criterion_update',
        ]);
    }

    public function test_formal_application_check_is_linked_and_does_not_change_application_status(): void
    {
        [$candidate, $registration, $household, , $housing, $contest] = $this->completeContext();
        $this->activeRuleSet($contest->program, $contest, [
            $this->criterion('registration_is_registered'),
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
        ]);
        $technician = $this->userWithRole('municipal_technician');

        $this->actingAs($technician)
            ->post(route('backoffice.eligibility.applications.run', $application))
            ->assertRedirect();

        $check = $application->fresh()->latestEligibilityCheck;
        $this->assertNotNull($check);
        $this->assertSame($application->id, $check->application_id);
        $this->assertSame(EligibilityCheckType::ApplicationFormalCheck, $check->check_type);
        $this->assertSame(ApplicationStatus::Submitted, $application->fresh()->status);
    }

    public function test_candidate_cannot_mass_assign_result_or_run_formal_check(): void
    {
        [$candidate, $registration, $household, , $housing, $contest] = $this->completeContext();
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
        ]);

        $check = EligibilityCheck::query()->create([
            'user_id' => $candidate->id,
            'check_type' => EligibilityCheckType::CandidatePreCheck->value,
            'result' => EligibilityResult::Eligible->value,
            'status' => 'completed',
        ]);
        $this->assertNull($check->result);

        $this->actingAs($candidate)
            ->post(route('backoffice.eligibility.applications.run', $application))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function completeContext(): array
    {
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create();
        $household = Household::factory()->candidate($registration)->create();
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'birth_date' => today()->subYears(35),
            'has_no_income' => false,
        ]);
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => 1000,
            'annual_amount' => 12000,
        ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'resides_in_municipality' => true,
            'works_in_municipality' => true,
        ]);
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();

        return [$candidate, $registration, $household, $member, $housing, $contest];
    }

    private function activeRuleSet(
        Program $program,
        ?Contest $contest = null,
        array $criteria = [],
    ): EligibilityRuleSet {
        $ruleSet = EligibilityRuleSet::factory()->active()->create([
            'program_id' => $program->id,
            'contest_id' => $contest?->id,
        ]);

        foreach ($criteria as $criterion) {
            $ruleSet->criteria()->create($criterion);
        }

        return $ruleSet;
    }

    private function criterion(string $code): array
    {
        return [
            'code' => $code,
            'name' => str($code)->replace('_', ' ')->title()->toString(),
            'description' => 'Critério fictício de teste.',
            'category' => EligibilityCriterionCategory::Other->value,
            'target' => 'calculated_value',
            'operator' => EligibilityOperator::IsTrue->value,
            'expected_value' => null,
            'minimum_value' => null,
            'maximum_value' => null,
            'unit' => null,
            'is_mandatory' => true,
            'requires_manual_review' => false,
            'failure_message' => 'Condição não cumprida.',
            'success_message' => 'Condição cumprida.',
            'review_message' => 'Requer análise municipal.',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
