<?php

namespace Tests\Feature\Security;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRunStatus;
use App\Models\AllocationRun;
use App\Models\DefinitiveList;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Allocation\AllocationEngine;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class AllocationRunsPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_allocation_run_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.runs.index' => 'permission:allocations.view',
            'backoffice.allocation.runs.create' => 'permission:allocations.create',
            'backoffice.allocation.runs.store' => 'permission:allocations.create',
            'backoffice.allocation.runs.show' => 'permission:allocations.view',
            'backoffice.allocation.runs.run' => 'permission:allocations.update',
            'backoffice.allocation.runs.lock' => 'permission:allocations.approve',
            'backoffice.allocation.runs.cancel' => 'permission:allocations.reject',
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

    public function test_view_permission_can_read_runs_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $run = AllocationRun::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.show', $run),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.run', $run),
        )->assertForbidden();
    }

    public function test_create_permission_can_create_run_but_not_transition_existing_runs(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $createdRun = AllocationRun::factory()->create();
        $existingRun = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->mock(
            AllocationEngine::class,
            function (MockInterface $mock) use ($createdRun): void {
                $mock->shouldReceive('run')
                    ->once()
                    ->withArgs(
                        fn (
                            array $data,
                            User $actor,
                        ): bool => array_key_exists('definitive_list_id', $data)
                            && $actor->id !== null,
                    )
                    ->andReturn($createdRun);
            },
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.create'),
        )->assertOk();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.store'),
            $this->validRunPayload(),
        )->assertRedirect(
            route('backoffice.allocation.runs.show', $createdRun),
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.lock', $existingRun),
        )->assertForbidden();
    }

    public function test_update_permission_can_access_run_action_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.run', $run),
        )->assertRedirect(
            route('backoffice.allocation.runs.show', $run),
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.lock', $run),
        )->assertForbidden();
    }

    public function test_approve_permission_can_lock_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->mock(
            AllocationEngine::class,
            function (MockInterface $mock) use ($run): void {
                $mock->shouldReceive('lock')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationRun $receivedRun,
                            User $actor,
                        ): bool => $receivedRun->is($run)
                            && $actor->id !== null,
                    )
                    ->andReturn($run);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.lock', $run),
        )->assertRedirect();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.cancel', $run),
            ['cancellation_reason' => 'Cancelamento de teste'],
        )->assertForbidden();
    }

    public function test_reject_permission_can_cancel_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.reject',
        ]);

        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->mock(
            AllocationEngine::class,
            function (MockInterface $mock) use ($run): void {
                $mock->shouldReceive('cancel')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationRun $receivedRun,
                            User $actor,
                        ): bool => $receivedRun->is($run)
                            && $actor->id !== null,
                    )
                    ->andReturn($run);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.cancel', $run),
            ['cancellation_reason' => 'Cancelamento de teste'],
        )->assertRedirect();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.lock', $run),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked_and_does_not_change_state(): void
    {
        $user = $this->userWithCustomRole([]);
        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.lock', $run),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.runs.cancel', $run),
            ['cancellation_reason' => 'Cancelamento de teste'],
        )->assertForbidden();

        $this->assertSame(
            AllocationRunStatus::Completed,
            $run->fresh()->status,
        );
    }

    public function test_candidate_is_blocked_even_with_all_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
            'allocations.reject',
        ]);

        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.runs.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.runs.show', $run),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.runs.lock', $run),
        )->assertForbidden();
    }

    public function test_auditor_can_read_but_cannot_create_run_lock_or_cancel(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
            'allocations.reject',
        ]);

        $run = AllocationRun::factory()->create([
            'status' => AllocationRunStatus::Completed,
        ]);

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.runs.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.runs.show', $run),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.runs.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.runs.lock', $run),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.runs.cancel', $run),
            ['cancellation_reason' => 'Cancelamento de teste'],
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

        $rejecter = $this->userWithCustomRole([
            'allocations.reject',
        ]);

        $run = AllocationRun::factory()->create();

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                AllocationRun::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewBackoffice',
                $run,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                AllocationRun::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $run,
            ),
        );

        $this->assertTrue(
            Gate::forUser($approver)->allows(
                'approveBackoffice',
                $run,
            ),
        );

        $this->assertTrue(
            Gate::forUser($rejecter)->allows(
                'rejectBackoffice',
                $run,
            ),
        );

        $this->assertFalse(
            Gate::forUser($updater)->allows(
                'approveBackoffice',
                $run,
            ),
        );

        $this->assertFalse(
            Gate::forUser($approver)->allows(
                'rejectBackoffice',
                $run,
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
     * @return array<string, mixed>
     */
    private function validRunPayload(): array
    {
        return [
            'definitive_list_id' => DefinitiveList::factory()->create()->id,
            'allocation_method' => AllocationMethod::Ranking->value,
            'notes' => 'Execução de teste.',
        ];
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
            'name' => 'allocation_runs_'.str()->random(8),
            'label' => 'Allocation runs permission test role',
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
