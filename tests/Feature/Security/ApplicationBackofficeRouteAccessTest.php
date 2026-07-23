<?php

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationBackofficeRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_routes_use_expected_permissions(): void
    {
        $expected = [
            'backoffice.applications.index' => 'permission:applications.view',

            'backoffice.applications.show' => 'permission:applications.view',

            'backoffice.applications.timeline' => 'permission:applications.audit,applications.view',
        ];

        foreach ($expected as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
            );

            $this->assertContains($permissionMiddleware, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_view_permission_can_access_index(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.applications.index'))
            ->assertOk();
    }

    public function test_custom_role_without_view_permission_cannot_access_index(): void
    {
        $user = $this->userWithCustomRole([
            'applications.create',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.applications.index'))
            ->assertForbidden();
    }

    public function test_custom_role_with_view_permission_can_access_show(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.applications.show', $application))
            ->assertOk();
    }

    public function test_custom_role_with_audit_permission_can_access_timeline(): void
    {
        $user = $this->userWithCustomRole([
            'applications.audit',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.applications.timeline', $application))
            ->assertOk();
    }

    public function test_custom_role_with_view_permission_can_access_timeline(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.applications.timeline', $application))
            ->assertOk();
    }

    public function test_candidate_cannot_access_backoffice_index_even_with_view_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
            ],
        );

        $this->actingAs($user)
            ->get(route('backoffice.applications.index'))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_backoffice_show_even_for_own_application(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.applications.show', $application))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_backoffice_timeline_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
                'applications.audit',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.applications.timeline', $application))
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
            'name' => 'application_route_'.str()->random(8),
            'label' => 'Application route test role',
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
