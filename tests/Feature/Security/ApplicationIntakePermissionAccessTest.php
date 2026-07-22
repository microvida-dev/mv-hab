<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationIntakePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_intake_routes_no_longer_use_fixed_role_middleware(): void
    {
        foreach ($this->routeNames() as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);

            $this->assertContains(
                'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
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
                "Route [{$routeName}] still uses fixed role middleware.",
            );

            $this->assertContains(
                'permission:administrative_processes.create',
                $middleware,
            );
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_create_permission_can_access_application_intake(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.create',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.application-intake.index'))
            ->assertOk();
    }

    public function test_user_without_create_permission_is_forbidden(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.application-intake.index'))
            ->assertForbidden();
    }

    public function test_candidate_remains_forbidden_even_with_create_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'candidate',
            permission: 'administrative_processes.create',
        );

        $this->actingAs($user)
            ->get(route('backoffice.application-intake.index'))
            ->assertForbidden();
    }

    public function test_auditor_remains_forbidden_by_policy_even_with_create_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'auditor',
            permission: 'administrative_processes.create',
        );

        $this->assertFalse(
            $user->can('create', \App\Models\AdministrativeProcess::class),
        );
    }

    /**
     * @return list<string>
     */
    private function routeNames(): array
    {
        return [
            'backoffice.application-intake.index',
            'backoffice.application-intake.create-process',
            'backoffice.application-intake.create-processes-batch',
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
            'name' => 'application_intake_'.str()->random(8),
            'label' => 'Application intake test role',
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

    private function userWithSystemRoleAndPermission(
        string $roleName,
        string $permission,
    ): User {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionId = Permission::query()
            ->where('name', $permission)
            ->value('id');

        $this->assertNotNull($permissionId);

        $role->permissions()->syncWithoutDetaching([$permissionId]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_unverified_auditor_is_redirected_to_mfa_before_policy_execution(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'auditor',
            permission: 'administrative_processes.create',
        );

        $this->actingAs($user)
            ->get(route('backoffice.application-intake.index'))
            ->assertRedirect();
    }
}
