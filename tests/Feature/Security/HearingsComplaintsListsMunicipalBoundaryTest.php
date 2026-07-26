<?php

namespace Tests\Feature\Security;

use App\Enums\ComplaintStatus;
use App\Enums\FeatureKey;
use App\Enums\HearingStatus;
use App\Enums\HearingType;
use App\Enums\ProvisionalListStatus;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Contest;
use App\Models\Hearing;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Program;
use App\Models\ProvisionalList;
use App\Models\RankingSnapshot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class HearingsComplaintsListsMunicipalBoundaryTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $this->municipalityB = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
    }

    public function test_indexes_and_details_are_scoped_to_actor_municipality(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'hearings.view',
            'complaints.view',
            'public_lists.view',
        ]);
        $localApplication = $this->applicationFor($this->municipalityA);
        $foreignApplication = $this->applicationFor($this->municipalityB);
        $localList = $this->provisionalListFor(
            $localApplication,
            'Lista municipal local',
        );
        $foreignList = $this->provisionalListFor(
            $foreignApplication,
            'Lista municipal externa',
        );
        $localHearing = Hearing::factory()->create([
            'application_id' => $localApplication->id,
            'user_id' => $localApplication->user_id,
            'subject' => 'Audiência municipal local',
        ]);
        $foreignHearing = Hearing::factory()->create([
            'application_id' => $foreignApplication->id,
            'user_id' => $foreignApplication->user_id,
            'subject' => 'Audiência municipal externa',
        ]);
        $localComplaint = Complaint::factory()->create([
            'provisional_list_id' => $localList->id,
            'application_id' => $localApplication->id,
            'user_id' => $localApplication->user_id,
            'subject' => 'Reclamação municipal local',
        ]);
        $foreignComplaint = Complaint::factory()->create([
            'provisional_list_id' => $foreignList->id,
            'application_id' => $foreignApplication->id,
            'user_id' => $foreignApplication->user_id,
            'subject' => 'Reclamação municipal externa',
        ]);

        $this->getAs($actor, route('backoffice.hearings.index'))
            ->assertOk()
            ->assertSee($localHearing->hearing_number)
            ->assertDontSee($foreignHearing->hearing_number);
        $this->getAs($actor, route('backoffice.complaints.index'))
            ->assertOk()
            ->assertSee($localComplaint->complaint_number)
            ->assertDontSee($foreignComplaint->complaint_number);
        $this->getAs($actor, route('backoffice.lists.provisional.index'))
            ->assertOk()
            ->assertSee($localList->list_number)
            ->assertDontSee($foreignList->list_number);
        $this->getAs(
            $actor,
            route('backoffice.lists.automation.index', $localApplication->contest_id),
        )->assertOk();

        $this->getAs(
            $actor,
            route('backoffice.hearings.show', $foreignHearing),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.complaints.show', $foreignComplaint),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.lists.provisional.show', $foreignList),
        )->assertForbidden();
        $this->getAs(
            $actor,
            route('backoffice.lists.automation.index', $foreignApplication->contest_id),
        )->assertForbidden();
    }

    public function test_foreign_application_cannot_be_injected_when_creating_hearing(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['hearings.create'],
        );
        $foreignApplication = $this->applicationFor($this->municipalityB);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.hearings.store'), [
                'application_id' => $foreignApplication->id,
                'hearing_type' => HearingType::Other->value,
                'subject' => 'Audiência injetada',
                'message' => 'Mensagem de teste.',
                'grounds' => 'Fundamentos de teste.',
                'deadline_at' => now()->addWeek()->toDateTimeString(),
                'candidate_visible' => false,
            ])
            ->assertSessionHasErrors('application_id');

        $this->assertDatabaseMissing('hearings', [
            'application_id' => $foreignApplication->id,
            'subject' => 'Audiência injetada',
        ]);
    }

    public function test_cross_municipality_mutations_are_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'hearings.issue',
            'complaints.mark_received',
            'public_lists.approve',
        ]);
        $foreignApplication = $this->applicationFor($this->municipalityB);
        $foreignList = $this->provisionalListFor(
            $foreignApplication,
            'Lista externa por aprovar',
            ProvisionalListStatus::UnderReview,
        );
        $foreignHearing = Hearing::factory()->create([
            'application_id' => $foreignApplication->id,
            'user_id' => $foreignApplication->user_id,
            'status' => HearingStatus::Draft->value,
        ]);
        $foreignComplaint = Complaint::factory()->create([
            'provisional_list_id' => $foreignList->id,
            'application_id' => $foreignApplication->id,
            'user_id' => $foreignApplication->user_id,
            'status' => ComplaintStatus::Submitted->value,
        ]);

        $this->postJsonAs(
            $actor,
            route('backoffice.hearings.issue', $foreignHearing),
        )->assertForbidden();
        $this->postJsonAs(
            $actor,
            route('backoffice.complaints.mark-received', $foreignComplaint),
        )->assertForbidden();
        $this->postJsonAs(
            $actor,
            route('backoffice.lists.provisional.approve', $foreignList),
        )->assertForbidden();

        $this->assertSame(HearingStatus::Draft, $foreignHearing->refresh()->status);
        $this->assertNull($foreignHearing->issued_at);
        $this->assertSame(
            ComplaintStatus::Submitted,
            $foreignComplaint->refresh()->status,
        );
        $this->assertNull($foreignComplaint->received_at);
        $this->assertSame(
            ProvisionalListStatus::UnderReview,
            $foreignList->refresh()->status,
        );
        $this->assertNull($foreignList->approved_at);
    }

    public function test_permissions_and_feature_entitlement_are_independent(): void
    {
        $withoutFeatureMunicipality = Municipality::factory()->create();
        $withoutFeature = $this->userWithPermissions(
            $withoutFeatureMunicipality,
            ['hearings.view'],
        );
        $withoutPermission = $this->userWithPermissions(
            $this->municipalityA,
            [],
        );
        $viewer = $this->userWithPermissions(
            $this->municipalityA,
            ['hearings.view'],
        );
        $application = $this->applicationFor($this->municipalityA);
        $hearing = Hearing::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'status' => HearingStatus::Draft->value,
        ]);

        $this->getAs($withoutFeature, route('backoffice.hearings.index'))
            ->assertForbidden();
        $this->getAs($withoutPermission, route('backoffice.hearings.index'))
            ->assertForbidden();
        $this->getAs($viewer, route('backoffice.hearings.show', $hearing))
            ->assertOk();
        $this->postJsonAs(
            $viewer,
            route('backoffice.hearings.issue', $hearing),
        )->assertForbidden();

        $this->assertSame(HearingStatus::Draft, $hearing->refresh()->status);
    }

    public function test_candidate_auditor_inactive_role_mfa_and_null_municipality_fail_closed(): void
    {
        $application = $this->applicationFor($this->municipalityA);
        $list = $this->provisionalListFor(
            $application,
            'Lista protegida',
            ProvisionalListStatus::UnderReview,
        );
        $candidate = $this->userWithPermissions(
            $this->municipalityA,
            ['public_lists.view'],
            systemRole: 'candidate',
        );
        $auditor = $this->userWithPermissions(
            $this->municipalityA,
            ['public_lists.approve'],
            systemRole: 'auditor',
        );
        $inactive = $this->userWithPermissions(
            $this->municipalityA,
            ['public_lists.view'],
            activeRole: false,
        );
        $mfaRequired = $this->userWithPermissions(
            $this->municipalityA,
            ['public_lists.view'],
            mfaRequired: true,
        );
        $withoutMunicipality = $this->userWithPermissions(
            $this->municipalityA,
            ['public_lists.view'],
        );
        $withoutMunicipality->forceFill(['municipality_id' => null])->save();

        $this->getAs(
            $candidate,
            route('backoffice.lists.provisional.show', $list),
        )->assertForbidden();
        $this->postJsonAs(
            $auditor,
            route('backoffice.lists.provisional.approve', $list),
        )->assertForbidden();
        $this->getAs(
            $inactive,
            route('backoffice.lists.provisional.show', $list),
        )->assertForbidden();
        $this->getAs(
            $withoutMunicipality,
            route('backoffice.lists.provisional.show', $list),
        )->assertForbidden();

        session()->forget('mfa.verified_at');

        $this->actingAs($mfaRequired)
            ->get(route('backoffice.lists.provisional.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $this->assertSame(
            ProvisionalListStatus::UnderReview,
            $list->refresh()->status,
        );
        $this->assertNull($list->approved_at);
    }

    public function test_hearing_issue_is_audited_and_cannot_be_repeated(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['hearings.issue'],
        );
        $application = $this->applicationFor($this->municipalityA);
        $hearing = Hearing::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'status' => HearingStatus::Draft->value,
        ]);

        $this->postAs(
            $actor,
            route('backoffice.hearings.issue', $hearing),
        )->assertSessionHas('success');

        $this->assertSame(HearingStatus::Open, $hearing->refresh()->status);
        $this->assertNotNull($hearing->issued_at);
        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'hearing_issue')->count(),
        );

        $this->postAs(
            $actor,
            route('backoffice.hearings.issue', $hearing),
        )->assertSessionHasErrors('hearing');

        $this->assertSame(HearingStatus::Open, $hearing->refresh()->status);
        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'hearing_issue')->count(),
        );
    }

    private function applicationFor(Municipality $municipality): Application
    {
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
        ]);
        $candidate = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);

        return Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
    }

    private function provisionalListFor(
        Application $application,
        string $title,
        ProvisionalListStatus $status = ProvisionalListStatus::Draft,
    ): ProvisionalList {
        $snapshot = RankingSnapshot::factory()->create([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);

        return ProvisionalList::factory()->create([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'ranking_snapshot_id' => $snapshot->id,
            'title' => $title,
            'status' => $status->value,
        ]);
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
            'name' => 'sprint_47d_'.str()->random(12),
            'label' => 'Teste 47D',
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

    /**
     * @return TestResponse<Response>
     */
    private function getAs(User $user, string $url): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($url);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<Response>
     */
    private function postAs(User $user, string $url, array $data = []): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post($url, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return TestResponse<Response>
     */
    private function postJsonAs(User $user, string $url, array $data = []): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->postJson($url, $data);
    }
}
