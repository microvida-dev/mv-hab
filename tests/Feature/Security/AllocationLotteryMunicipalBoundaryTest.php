<?php

namespace Tests\Feature\Security;

use App\Enums\AllocationMethod;
use App\Enums\DefinitiveListStatus;
use App\Enums\FeatureKey;
use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Enums\LotteryDrawStatus;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\LotteryDraw;
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

class AllocationLotteryMunicipalBoundaryTest extends TestCase
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

    public function test_lottery_index_and_detail_are_scoped_to_actor_municipality(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['lotteries.view'],
        );
        [, $localContest, , $localDraw] = $this->lotteryContext(
            $this->municipalityA,
            'Concurso de sorteio local',
        );
        [, $foreignContest, , $foreignDraw] = $this->lotteryContext(
            $this->municipalityB,
            'Concurso de sorteio externo',
        );

        $this->getAs($actor, route('backoffice.lottery-draws.index'))
            ->assertOk()
            ->assertSee($localContest->title)
            ->assertDontSee($foreignContest->title);
        $this->getAs(
            $actor,
            route('backoffice.lottery-draws.show', $foreignDraw),
        )->assertForbidden();

        $this->assertNotSame($localDraw->id, $foreignDraw->id);
    }

    public function test_foreign_allocation_run_cannot_be_injected_when_creating_draw(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['lotteries.create'],
        );
        [, , $foreignRun] = $this->lotteryContext(
            $this->municipalityB,
            'Concurso externo para injeção',
        );
        $drawCount = LotteryDraw::query()->count();

        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.store'),
            [
                'allocation_run_id' => $foreignRun->id,
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'location' => 'Local injetado',
            ],
        )->assertSessionHasErrors('allocation_run_id');

        $this->assertSame($drawCount, LotteryDraw::query()->count());
    }

    public function test_cross_municipality_lottery_mutations_are_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'lotteries.run',
            'lotteries.validate',
            'lotteries.cancel',
            'lotteries.convocations.generate',
        ]);
        [, , , $foreignDraw] = $this->lotteryContext(
            $this->municipalityB,
            'Concurso externo protegido',
        );

        $this->postJsonAs(
            $actor,
            route('backoffice.lottery-draws.run', $foreignDraw),
            ['seed' => 'FOREIGN-SEED'],
        )->assertForbidden();
        $this->postJsonAs(
            $actor,
            route('backoffice.lottery-draws.validate', $foreignDraw),
        )->assertForbidden();
        $this->postJsonAs(
            $actor,
            route('backoffice.lottery-draws.cancel', $foreignDraw),
            ['reason' => 'Tentativa fora do município.'],
        )->assertForbidden();
        $this->postJsonAs(
            $actor,
            route('backoffice.lottery-draws.convocations.generate', $foreignDraw),
            [
                'scheduled_for' => now()->addDays(2)->toDateTimeString(),
                'location' => 'Sala externa',
            ],
        )->assertForbidden();

        $this->assertSame(
            LotteryDrawStatus::Draft,
            $foreignDraw->refresh()->status,
        );
        $this->assertNull($foreignDraw->started_at);
        $this->assertNull($foreignDraw->validated_at);
        $this->assertNull($foreignDraw->cancelled_at);
        $this->assertSame(0, $foreignDraw->convocations()->count());
    }

    public function test_draw_cannot_be_validated_early_or_executed_twice(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'lotteries.participants.load',
            'lotteries.participants.lock',
            'lotteries.run',
            'lotteries.validate',
        ]);
        [, , , $draw] = $this->lotteryContext(
            $this->municipalityA,
            'Concurso para idempotência',
        );

        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.validate', $draw),
        )->assertSessionHasErrors('lottery_draw');
        $this->assertSame(LotteryDrawStatus::Draft, $draw->refresh()->status);

        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.participants.load', $draw),
        )
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.participants.lock', $draw),
        )
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.run', $draw),
            ['seed' => 'SPRINT-47D-SEED'],
        )
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $draw->refresh();
        $resultIds = $draw->results()->pluck('id')->all();
        $resultHash = $draw->result_hash;

        $this->assertSame(LotteryDrawStatus::Completed, $draw->status);
        $this->assertCount(1, $resultIds);
        $this->assertNotNull($resultHash);
        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'lottery_draw_run')->count(),
        );

        $this->postAs(
            $actor,
            route('backoffice.lottery-draws.run', $draw),
            ['seed' => 'SECOND-SEED'],
        )->assertSessionHasErrors('lottery_draw');

        $draw->refresh();
        $this->assertSame(LotteryDrawStatus::Completed, $draw->status);
        $this->assertSame($resultHash, $draw->result_hash);
        $this->assertSame($resultIds, $draw->results()->pluck('id')->all());
        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'lottery_draw_run')->count(),
        );
    }

    public function test_lottery_permissions_feature_candidate_auditor_and_mfa_fail_closed(): void
    {
        [, , , $draw] = $this->lotteryContext(
            $this->municipalityA,
            'Concurso transversal protegido',
        );
        $withoutFeatureMunicipality = Municipality::factory()->create();
        $withoutFeature = $this->userWithPermissions(
            $withoutFeatureMunicipality,
            ['lotteries.view'],
        );
        $withoutPermission = $this->userWithPermissions(
            $this->municipalityA,
            [],
        );
        $candidate = $this->userWithPermissions(
            $this->municipalityA,
            ['lotteries.view'],
            systemRole: 'candidate',
        );
        $auditor = $this->userWithPermissions(
            $this->municipalityA,
            ['lotteries.cancel'],
            systemRole: 'auditor',
        );
        $mfaRequired = $this->userWithPermissions(
            $this->municipalityA,
            ['lotteries.view'],
            mfaRequired: true,
        );

        $this->getAs($withoutFeature, route('backoffice.lottery-draws.index'))
            ->assertForbidden();
        $this->getAs($withoutPermission, route('backoffice.lottery-draws.index'))
            ->assertForbidden();
        $this->getAs(
            $candidate,
            route('backoffice.lottery-draws.show', $draw),
        )->assertForbidden();
        $this->postJsonAs(
            $auditor,
            route('backoffice.lottery-draws.cancel', $draw),
            ['reason' => 'Auditor não pode cancelar.'],
        )->assertForbidden();

        session()->forget('mfa.verified_at');

        $this->actingAs($mfaRequired)
            ->get(route('backoffice.lottery-draws.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $this->assertSame(LotteryDrawStatus::Draft, $draw->refresh()->status);
        $this->assertNull($draw->cancelled_at);
    }

    /**
     * @return array{Program, Contest, AllocationRun, LotteryDraw}
     */
    private function lotteryContext(
        Municipality $municipality,
        string $contestTitle,
    ): array {
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
            'title' => $contestTitle,
        ]);
        $candidate = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
        $snapshot = RankingSnapshot::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
        $provisionalList = ProvisionalList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'ranking_snapshot_id' => $snapshot->id,
        ]);
        $definitiveList = DefinitiveList::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'provisional_list_id' => $provisionalList->id,
            'status' => DefinitiveListStatus::Locked->value,
        ]);
        DefinitiveListEntry::factory()->create([
            'definitive_list_id' => $definitiveList->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'entry_type' => ListEntryType::Ranked->value,
            'status' => ListEntryStatus::Ranked->value,
            'rank_position' => 1,
        ]);
        $ruleSet = AllocationRuleSet::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'allocation_method' => AllocationMethod::Lottery->value,
        ]);
        $allocationRun = AllocationRun::factory()->create([
            'allocation_rule_set_id' => $ruleSet->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'definitive_list_id' => $definitiveList->id,
            'allocation_method' => AllocationMethod::Lottery->value,
        ]);
        $draw = LotteryDraw::factory()->create([
            'allocation_run_id' => $allocationRun->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'definitive_list_id' => $definitiveList->id,
            'status' => LotteryDrawStatus::Draft->value,
        ]);

        return [$program, $contest, $allocationRun, $draw];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(
        Municipality $municipality,
        array $permissions,
        bool $mfaRequired = false,
        ?string $systemRole = null,
    ): User {
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'sprint_47d_lottery_'.str()->random(12),
            'label' => 'Teste sorteios 47D',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
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
