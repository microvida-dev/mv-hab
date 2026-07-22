<?php

namespace Tests\Feature\Security;

use App\Models\Contest;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProgramContestPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_program_and_contest_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'admin.programs.index' => 'permission:programs.view',
            'admin.programs.create' => 'permission:programs.create',
            'admin.programs.store' => 'permission:programs.create',
            'admin.programs.show' => 'permission:programs.view',
            'admin.programs.edit' => 'permission:programs.update',
            'admin.programs.update' => 'permission:programs.update',
            'admin.programs.destroy' => 'permission:programs.delete',
            'admin.programs.publish' => 'permission:programs.publish',

            'admin.contests.index' => 'permission:contests.view',
            'admin.contests.create' => 'permission:contests.create',
            'admin.contests.store' => 'permission:contests.create',
            'admin.contests.show' => 'permission:contests.view',
            'admin.contests.edit' => 'permission:contests.update',
            'admin.contests.update' => 'permission:contests.update',
            'admin.contests.destroy' => 'permission:contests.delete',
            'admin.contests.publish' => 'permission:contests.publish',
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
                "Route [{$routeName}] does not exclude the inherited fixed role middleware.",
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
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
                "Route [{$routeName}] still contains active fixed role middleware.",
            );
        }
    }

    public function test_user_with_program_view_permission_can_access_program_pages(): void
    {
        $user = $this->userWithCustomRole([
            'programs.view',
        ]);

        $program = Program::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.show', $program))
            ->assertOk();
    }

    public function test_user_with_contest_view_permission_can_access_contest_pages(): void
    {
        $user = $this->userWithCustomRole([
            'contests.view',
        ]);

        $contest = Contest::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.show', $contest))
            ->assertOk();
    }

    public function test_user_without_view_permissions_cannot_access_program_or_contest_indexes(): void
    {
        $user = $this->userWithCustomRole([]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertForbidden();
    }

    public function test_program_and_contest_view_permissions_are_isolated(): void
    {
        $programViewer = $this->userWithCustomRole([
            'programs.view',
        ]);

        $this->actingAs($programViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertOk();

        $this->actingAs($programViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertForbidden();

        $contestViewer = $this->userWithCustomRole([
            'contests.view',
        ]);

        $this->actingAs($contestViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertOk();

        $this->actingAs($contestViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_program_and_contest_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'programs.view',
            'programs.create',
            'programs.update',
            'programs.delete',
            'programs.publish',
            'contests.view',
            'contests.create',
            'contests.update',
            'contests.delete',
            'contests.publish',
        ]);

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertForbidden();
    }

    public function test_auditor_can_view_programs_and_contests_but_cannot_change_them(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'programs.view',
            'programs.create',
            'programs.update',
            'programs.delete',
            'programs.publish',
            'contests.view',
            'contests.create',
            'contests.update',
            'contests.delete',
            'contests.publish',
        ]);

        $program = Program::factory()->create();
        $contest = Contest::factory()->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.show', $program))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.show', $contest))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.edit', $program))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('admin.programs.destroy', $program))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.edit', $contest))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('admin.contests.destroy', $contest))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.contests.publish', $contest))
            ->assertForbidden();
    }

    public function test_update_permission_does_not_grant_program_publish_permission(): void
    {
        $user = $this->userWithCustomRole([
            'programs.update',
        ]);

        $program = Program::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.programs.edit', $program))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.programs.publish', $program))
            ->assertForbidden();
    }

    public function test_update_permission_does_not_grant_contest_publish_permission(): void
    {
        $user = $this->userWithCustomRole([
            'contests.update',
        ]);

        $contest = Contest::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.contests.edit', $contest))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('admin.contests.publish', $contest))
            ->assertForbidden();
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
            'name' => 'program_contest_'.str()->random(8),
            'label' => 'Program and contest test role',
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
