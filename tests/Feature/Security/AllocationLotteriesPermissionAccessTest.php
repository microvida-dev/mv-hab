<?php

namespace Tests\Feature\Security;

use App\Enums\LotteryRunStatus;
use App\Models\LotteryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AllocationLotteriesPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_lottery_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.lotteries.index' => 'permission:allocations.view',
            'backoffice.allocation.lotteries.create' => 'permission:allocations.create',
            'backoffice.allocation.lotteries.store' => 'permission:allocations.create',
            'backoffice.allocation.lotteries.show' => 'permission:allocations.view',
            'backoffice.allocation.lotteries.run' => 'permission:allocations.update',
            'backoffice.allocation.lotteries.lock' => 'permission:allocations.approve',
            'backoffice.allocation.lotteries.audit' => 'permission:allocations.audit',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
                "Route [{$routeName}] does not exclude inherited fixed-role middleware.",
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains($permissionMiddleware, $middleware);

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                "Route [{$routeName}] still contains active role middleware.",
            );
        }
    }

    public function test_view_permission_can_read_lotteries_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $lottery = LotteryRun::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.show', $lottery),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.create'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertForbidden();
    }

    public function test_create_permission_can_create_lottery_but_not_update_or_audit(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $lottery = LotteryRun::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.create'),
        )->assertOk();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.store'),
            [],
        )->assertSessionHasErrors('allocation_run_id');

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.run', $lottery),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertForbidden();
    }

    public function test_update_permission_can_access_run_action_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.run', $lottery),
        )->assertRedirect(
            route('backoffice.allocation.lotteries.show', $lottery),
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertForbidden();
    }

    public function test_approve_permission_can_lock_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertRedirect();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertForbidden();
    }

    public function test_audit_permission_can_access_audit_without_mutation_access(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.audit',
        ]);

        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked_and_does_not_change_state(): void
    {
        $user = $this->userWithCustomRole([]);
        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertForbidden();

        $this->assertSame(
            LotteryRunStatus::Completed,
            $lottery->fresh()->status,
        );
    }

    public function test_candidate_is_blocked_even_with_all_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
            'allocations.audit',
        ]);

        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.lotteries.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertForbidden();
    }

    public function test_auditor_can_read_and_audit_but_cannot_create_run_or_lock(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
            'allocations.audit',
        ]);

        $lottery = LotteryRun::factory()->create([
            'status' => LotteryRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.lotteries.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.lotteries.show', $lottery),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.lotteries.audit', $lottery),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.lotteries.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.lotteries.lock', $lottery),
        )->assertForbidden();
    }

    public function test_backoffice_policy_abilities_match_permission_and_role_boundaries(): void
    {
        $viewer = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $creator = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $updater = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $approver = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $auditor = $this->userWithCustomRole([
            'allocations.audit',
        ]);

        $lottery = LotteryRun::factory()->create();

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                LotteryRun::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewBackoffice',
                $lottery,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                LotteryRun::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $lottery,
            ),
        );

        $this->assertTrue(
            Gate::forUser($approver)->allows(
                'approveBackoffice',
                $lottery,
            ),
        );

        $this->assertTrue(
            Gate::forUser($auditor)->allows(
                'auditBackoffice',
                $lottery,
            ),
        );

        $this->assertFalse(
            Gate::forUser($viewer)->allows(
                'auditBackoffice',
                $lottery,
            ),
        );

        $this->assertFalse(
            Gate::forUser($approver)->allows(
                'updateBackoffice',
                $lottery,
            ),
        );
    }

    private function getAsBackofficeUser(
        User $user,
        string $uri,
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($uri);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postAsBackofficeUser(
        User $user,
        string $uri,
        array $data = [],
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post($uri, $data);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'allocation_lotteries_'.str()->random(8),
            'label' => 'Allocation lotteries permission test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithSystemRoleAndPermissions(
        string $roleName,
        array $permissions,
    ): User {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
