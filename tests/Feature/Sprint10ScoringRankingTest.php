<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationScoreStatus;
use App\Enums\ApplicationStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use App\Enums\ScoringRuleSetStatus;
use App\Enums\TieBreakerDirection;
use App\Models\AdhesionRegistration;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\Contest;
use App\Models\CurrentHousingSituation;
use App\Models\EligibilityCheck;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\Program;
use App\Models\RankingSnapshot;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Models\User;
use App\Services\Scoring\ScoringEngine;
use App\Services\Scoring\ScoringRuleSetResolver;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint10ScoringRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_scoring_access_is_protected_by_role_and_permission(): void
    {
        $this->get(route('backoffice.scoring.rule-sets.index'))
            ->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->actingAs($candidate)
            ->get(route('backoffice.scoring.rule-sets.index'))
            ->assertForbidden();

        $technician = $this->userWithRole('municipal_technician');
        $this->actingAs($technician)
            ->get(route('backoffice.scoring.rule-sets.index'))
            ->assertOk();
    }

    public function test_admin_can_create_update_activate_and_archive_scoring_rule_set_with_audit(): void
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->create();

        $this->actingAs($administrator)
            ->post(route('backoffice.scoring.rule-sets.store'), [
                'program_id' => $program->id,
                'name' => 'Matriz de teste',
                'status' => ScoringRuleSetStatus::Draft->value,
                'is_default' => '1',
            ])
            ->assertRedirect();

        $ruleSet = ScoringRuleSet::query()->firstOrFail();
        $this->assertSame(ScoringRuleSetStatus::Draft, $ruleSet->status);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'scoring',
            'action' => 'rule_set_create',
        ]);

        $this->actingAs($administrator)
            ->put(route('backoffice.scoring.rule-sets.update', $ruleSet), [
                'program_id' => $program->id,
                'name' => 'Matriz atualizada',
                'status' => ScoringRuleSetStatus::Draft->value,
            ])
            ->assertRedirect();

        $this->actingAs($administrator)
            ->post(route('backoffice.scoring.rule-sets.activate', $ruleSet))
            ->assertRedirect();
        $this->assertSame(ScoringRuleSetStatus::Active, $ruleSet->fresh()->status);

        $this->actingAs($administrator)
            ->post(route('backoffice.scoring.rule-sets.archive', $ruleSet))
            ->assertRedirect();
        $this->assertSame(ScoringRuleSetStatus::Archived, $ruleSet->fresh()->status);
    }

    public function test_contest_rule_set_precedes_program_and_inactive_sets_are_ignored(): void
    {
        $program = Program::factory()->create();
        $contest = Contest::factory()->for($program)->create();
        $programSet = ScoringRuleSet::factory()->active()->for($program)->create();
        $contestSet = ScoringRuleSet::factory()->active()->for($program)->for($contest)->create();

        $resolver = app(ScoringRuleSetResolver::class);
        $this->assertTrue($resolver->resolve($program, $contest)->is($contestSet));

        $contestSet->forceFill(['status' => ScoringRuleSetStatus::Draft])->save();
        $this->assertTrue($resolver->resolve($program, $contest)->is($programSet));

        $programSet->forceFill(['status' => ScoringRuleSetStatus::Archived])->save();
        $this->assertNull($resolver->resolve($program, $contest));
    }

    public function test_scoring_run_scores_only_admitted_and_eligible_applications_and_creates_snapshot(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $ruleSet = $this->activeRuleSet($program, $contest, includeManual: true);
        $eligibleApplication = $this->submittedApplication($contest, monthlyIncome: 900);
        $notAdmittedApplication = $this->submittedApplication($contest, monthlyIncome: 700, admitted: false);
        $ineligibleApplication = $this->submittedApplication($contest, monthlyIncome: 600, eligibility: EligibilityResult::Ineligible);

        $this->actingAs($technician);
        $run = app(ScoringEngine::class)->run($technician, contest: $contest);

        $this->assertSame($ruleSet->id, $run->scoring_rule_set_id);
        $this->assertSame(2, $run->total_applications);
        $this->assertSame(1, $run->scored_applications);
        $this->assertSame(1, ApplicationScore::query()->count());
        $this->assertDatabaseHas('application_scores', [
            'application_id' => $eligibleApplication->id,
            'status' => ApplicationScoreStatus::RequiresManualReview->value,
        ]);
        $this->assertDatabaseMissing('application_scores', ['application_id' => $notAdmittedApplication->id]);
        $this->assertDatabaseMissing('application_scores', ['application_id' => $ineligibleApplication->id]);
        $this->assertSame(2, ApplicationScore::query()->first()->details()->count());
        $this->assertSame(1, RankingSnapshot::query()->count());
        $this->assertDatabaseHas('ranking_entries', [
            'application_id' => $eligibleApplication->id,
            'rank_position' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'scoring',
            'action' => 'scoring_run_execute',
        ]);
    }

    public function test_manual_score_is_validated_updates_total_and_locked_scores_cannot_be_edited(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $this->activeRuleSet($program, $contest, includeManual: true);
        $this->submittedApplication($contest, monthlyIncome: 900);

        $this->actingAs($technician);
        app(ScoringEngine::class)->run($technician, contest: $contest);
        $score = ApplicationScore::query()->with('details')->firstOrFail();
        $manualDetail = $score->details->firstWhere('requires_manual_review', true);

        $this->put(route('backoffice.scoring.application-scores.manual-review.update', $score), [
            'application_score_detail_id' => $manualDetail->id,
            'manual_points' => 99,
            'manual_notes' => 'Valor fictício acima do limite.',
        ])->assertSessionHasErrors('manual_points');

        $this->put(route('backoffice.scoring.application-scores.manual-review.update', $score), [
            'application_score_detail_id' => $manualDetail->id,
            'manual_points' => 4,
            'manual_notes' => 'Apreciação técnica fictícia.',
        ])->assertRedirect();

        $this->assertSame('14.00', $score->fresh()->total_score);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'scoring',
            'action' => 'manual_score_update',
        ]);

        $this->post(route('backoffice.scoring.application-scores.lock', $score))
            ->assertRedirect();

        $this->put(route('backoffice.scoring.application-scores.manual-review.update', $score), [
            'application_score_detail_id' => $manualDetail->id,
            'manual_points' => 3,
        ])->assertSessionHasErrors('application_score');
    }

    public function test_ranking_orders_by_score_and_configured_tie_breaker(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $program = Program::factory()->published()->create();
        $contest = Contest::factory()->for($program)->open()->create();
        $ruleSet = $this->activeRuleSet($program, $contest);
        $ruleSet->tieBreakerRules()->create([
            'code' => 'monthly_income_per_capita',
            'name' => 'Menor rendimento per capita',
            'target' => 'monthly_income_per_capita',
            'direction' => TieBreakerDirection::Asc->value,
            'priority_order' => 10,
            'is_active' => true,
        ]);
        $lowerIncome = $this->submittedApplication($contest, monthlyIncome: 500);
        $higherIncome = $this->submittedApplication($contest, monthlyIncome: 900);

        $this->actingAs($technician);
        app(ScoringEngine::class)->run($technician, contest: $contest);

        $this->assertSame(1, ApplicationScore::query()->where('application_id', $lowerIncome->id)->value('rank_position'));
        $this->assertSame(2, ApplicationScore::query()->where('application_id', $higherIncome->id)->value('rank_position'));
        $this->assertDatabaseHas('ranking_entries', [
            'application_id' => $lowerIncome->id,
            'rank_position' => 1,
        ]);
    }

    public function test_candidate_cannot_create_scoring_run_or_view_internal_ranking_or_technical_messages(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->post(route('backoffice.scoring.runs.store'), [])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->get(route('backoffice.scoring.ranking-snapshots.index'))
            ->assertForbidden();

        $this->get('/ranking-snapshots')->assertNotFound();
    }

    private function activeRuleSet(Program $program, Contest $contest, bool $includeManual = false): ScoringRuleSet
    {
        $ruleSet = ScoringRuleSet::factory()->active()->for($program)->for($contest)->create();
        ScoringCriterion::factory()->create([
            'scoring_rule_set_id' => $ruleSet->id,
            'code' => 'household_size',
            'name' => 'Agregado preenchido',
            'category' => 'household',
            'target' => 'household',
            'calculation_type' => ScoringCalculationType::FixedPoints->value,
            'operator' => ScoringOperator::Exists->value,
            'points' => 10,
            'max_points' => 10,
            'sort_order' => 10,
        ]);

        if ($includeManual) {
            ScoringCriterion::factory()->manual()->create([
                'scoring_rule_set_id' => $ruleSet->id,
                'code' => 'manual_assessment',
                'name' => 'Apreciação manual',
                'max_points' => 10,
                'sort_order' => 20,
            ]);
        }

        return $ruleSet;
    }

    private function submittedApplication(
        Contest $contest,
        float $monthlyIncome,
        bool $admitted = true,
        EligibilityResult $eligibility = EligibilityResult::Eligible,
    ): Application {
        $candidate = $this->userWithRole('candidate');
        $registration = AdhesionRegistration::factory()->registered()->for($candidate)->create([
            'nif' => 'TEST-S10-'.fake()->unique()->numerify('#####'),
        ]);
        $household = Household::factory()->candidate($registration)->create();
        $member = HouseholdMember::factory()->applicant()->create([
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
        ]);
        IncomeRecord::factory()->create([
            'household_member_id' => $member->id,
            'household_id' => $household->id,
            'adhesion_registration_id' => $registration->id,
            'monthly_amount' => $monthlyIncome,
            'annual_amount' => $monthlyIncome * 12,
        ]);
        $housing = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
        ]);

        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $housing->id,
            'status' => ApplicationStatus::Submitted->value,
        ]);

        if ($admitted) {
            AdministrativeProcess::factory()->create([
                'application_id' => $application->id,
                'program_id' => $contest->program_id,
                'contest_id' => $contest->id,
                'user_id' => $candidate->id,
                'status' => AdministrativeProcessStatus::AdmittedForScoring->value,
                'admitted_for_scoring_at' => now(),
            ]);
        }

        EligibilityCheck::factory()->create([
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'application_id' => $application->id,
            'adhesion_registration_id' => $registration->id,
            'user_id' => $candidate->id,
            'check_type' => EligibilityCheckType::ApplicationFormalCheck->value,
            'result' => $eligibility->value,
        ]);

        return $application->fresh();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
