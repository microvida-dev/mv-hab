<?php

namespace Tests\Feature\Security;

use App\Enums\AdministrativeDecisionStatus;
use App\Enums\ApplicationScoreStatus;
use App\Enums\EligibilityRuleSetStatus;
use App\Enums\FeatureKey;
use App\Enums\ScoringRuleSetStatus;
use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class EligibilityScoringMunicipalBoundaryTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = $this->municipalityWithFeatures(FeatureKey::ApplicationReview);
        $this->municipalityB = $this->municipalityWithFeatures(FeatureKey::ApplicationReview);
    }

    public function test_technical_configuration_is_scoped_without_application_entitlement(): void
    {
        $municipalityWithoutFeature = Municipality::factory()->create();
        $actor = $this->userWithPermissions(
            $municipalityWithoutFeature,
            ['eligibility.view', 'scoring.view'],
        );
        $localProgram = Program::factory()->create([
            'municipality_id' => $municipalityWithoutFeature->id,
        ]);
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $localEligibility = EligibilityRuleSet::factory()->create([
            'program_id' => $localProgram->id,
            'name' => 'Elegibilidade técnica local',
        ]);
        $foreignEligibility = EligibilityRuleSet::factory()->create([
            'program_id' => $foreignProgram->id,
            'name' => 'Elegibilidade técnica externa',
        ]);
        $localScoring = ScoringRuleSet::factory()->create([
            'program_id' => $localProgram->id,
            'name' => 'Classificação técnica local',
        ]);
        $foreignScoring = ScoringRuleSet::factory()->create([
            'program_id' => $foreignProgram->id,
            'name' => 'Classificação técnica externa',
        ]);
        $localCriterion = ScoringCriterion::factory()->create([
            'scoring_rule_set_id' => $localScoring->id,
        ]);
        $foreignCriterion = ScoringCriterion::factory()->create([
            'scoring_rule_set_id' => $foreignScoring->id,
        ]);

        $this->getAs($actor, route('backoffice.eligibility.rule-sets.index'))
            ->assertOk()
            ->assertSee($localEligibility->name)
            ->assertDontSee($foreignEligibility->name);
        $this->getAs($actor, route('backoffice.scoring.rule-sets.index'))
            ->assertOk()
            ->assertSee($localScoring->name)
            ->assertDontSee($foreignScoring->name);
        $this->getAs(
            $actor,
            route('backoffice.eligibility.rule-sets.show', $foreignEligibility),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.scoring.rule-sets.show', $foreignScoring),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.scoring.rules.index', $localCriterion),
        )->assertOk();
        $this->getAs(
            $actor,
            route('backoffice.scoring.rules.index', $foreignCriterion),
        )->assertForbidden();
    }

    public function test_foreign_program_cannot_be_injected_into_local_rule_set(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['eligibility.create'],
        );
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.eligibility.rule-sets.store'), [
                'program_id' => $foreignProgram->id,
                'name' => 'Regra injetada',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('program_id');

        $this->assertDatabaseMissing('eligibility_rule_sets', [
            'program_id' => $foreignProgram->id,
            'name' => 'Regra injetada',
        ]);
    }

    public function test_create_and_update_permissions_cannot_transition_rule_set_statuses(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            [
                'eligibility.create',
                'eligibility.update',
                'scoring.create',
                'scoring.update',
            ],
        );
        $program = Program::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.eligibility.rule-sets.store'), [
                'program_id' => $program->id,
                'name' => 'Elegibilidade sem transição implícita',
                'status' => EligibilityRuleSetStatus::Active->value,
            ])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $eligibility = EligibilityRuleSet::query()
            ->where('name', 'Elegibilidade sem transição implícita')
            ->firstOrFail();
        $this->assertSame(EligibilityRuleSetStatus::Draft, $eligibility->status);

        $eligibility->forceFill(['status' => EligibilityRuleSetStatus::Active])->save();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('backoffice.eligibility.rule-sets.update', $eligibility), [
                'program_id' => $program->id,
                'name' => 'Elegibilidade editada',
                'status' => EligibilityRuleSetStatus::Archived->value,
            ])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertSame(EligibilityRuleSetStatus::Active, $eligibility->refresh()->status);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.scoring.rule-sets.store'), [
                'program_id' => $program->id,
                'name' => 'Classificação sem transição implícita',
                'status' => ScoringRuleSetStatus::Active->value,
            ])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $scoring = ScoringRuleSet::query()
            ->where('name', 'Classificação sem transição implícita')
            ->firstOrFail();
        $this->assertSame(ScoringRuleSetStatus::Draft, $scoring->status);

        $scoring->forceFill(['status' => ScoringRuleSetStatus::Active])->save();
        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('backoffice.scoring.rule-sets.update', $scoring), [
                'program_id' => $program->id,
                'name' => 'Classificação editada',
                'status' => ScoringRuleSetStatus::Archived->value,
            ])
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertSame(ScoringRuleSetStatus::Active, $scoring->refresh()->status);
    }

    public function test_operational_scoring_requires_feature_and_run_permission_is_independent(): void
    {
        $municipalityWithoutFeature = Municipality::factory()->create();
        $withoutFeature = $this->userWithPermissions(
            $municipalityWithoutFeature,
            ['scoring.view'],
        );
        $runner = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.run'],
        );

        $this->getAs($withoutFeature, route('backoffice.scoring.runs.index'))
            ->assertForbidden();
        $this->assertFalse($runner->hasPermission('scoring.approve'));
        $this->assertFalse($runner->hasPermission('scoring.lock'));
        $this->getAs($runner, route('backoffice.scoring.runs.create'))
            ->assertOk();
    }

    public function test_cross_municipality_decision_approval_is_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['administrative_decisions.approve'],
        );
        [$application, $process] = $this->applicationProcessFor($this->municipalityB);
        $decision = AdministrativeDecision::factory()->create([
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'status' => AdministrativeDecisionStatus::Proposed->value,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->postJson(
                route('backoffice.administrative-decisions.approve', $decision),
                ['confirm_decision' => true],
            )
            ->assertForbidden();

        $this->assertSame(
            AdministrativeDecisionStatus::Proposed,
            $decision->refresh()->status,
        );
        $this->assertNull($decision->approved_at);
    }

    public function test_review_does_not_grant_lock_and_candidate_auditor_inactive_and_mfa_fail_closed(): void
    {
        [$application] = $this->applicationProcessFor($this->municipalityA);
        $ruleSet = ScoringRuleSet::factory()->create([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);
        $run = ScoringRun::factory()->create([
            'scoring_rule_set_id' => $ruleSet->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);
        $score = ApplicationScore::factory()->create([
            'application_id' => $application->id,
            'scoring_rule_set_id' => $ruleSet->id,
            'scoring_run_id' => $run->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'status' => ApplicationScoreStatus::Calculated->value,
        ]);
        $reviewer = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.review'],
        );
        $candidate = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.view'],
            systemRole: 'candidate',
        );
        $auditor = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.lock'],
            systemRole: 'auditor',
        );
        $inactive = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.view'],
            activeRole: false,
        );
        $mfaRequired = $this->userWithPermissions(
            $this->municipalityA,
            ['scoring.run'],
            mfaRequired: true,
        );

        $this->getAs(
            $reviewer,
            route('backoffice.scoring.application-scores.manual-review', $score),
        )->assertOk();
        $this->actingAs($reviewer)
            ->withSession(['mfa.verified_at' => now()])
            ->postJson(route('backoffice.scoring.application-scores.lock', $score))
            ->assertForbidden();
        $this->assertSame(ApplicationScoreStatus::Calculated, $score->refresh()->status);

        $this->getAs(
            $candidate,
            route('backoffice.scoring.application-scores.show', $score),
        )->assertForbidden();
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->postJson(route('backoffice.scoring.application-scores.lock', $score))
            ->assertForbidden();
        $this->getAs(
            $inactive,
            route('backoffice.scoring.application-scores.show', $score),
        )->assertForbidden();

        session()->forget('mfa.verified_at');

        $this->actingAs($mfaRequired)
            ->get(route('backoffice.scoring.runs.create'))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    /**
     * @return array{0: Application, 1: AdministrativeProcess}
     */
    private function applicationProcessFor(Municipality $municipality): array
    {
        $program = Program::factory()->create(['municipality_id' => $municipality->id]);
        $contest = Contest::factory()->create(['program_id' => $program->id]);
        $candidate = User::factory()->create(['municipality_id' => $municipality->id]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
        ]);

        return [$application, $process];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(
        Municipality $municipality,
        array $permissions,
        bool $activeRole = true,
        bool $mfaRequired = false,
        ?string $systemRole = null,
    ): User {
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'sprint_47c_'.str()->random(12),
            'label' => 'Teste 47C',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $activeRole,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => $mfaRequired,
        ]);
        $user->roles()->attach($role);

        if (is_string($systemRole)) {
            $user->assignRole($systemRole);
        }

        return $user;
    }

    private function getAs(User $user, string $url): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($url);
    }
}
