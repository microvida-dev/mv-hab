<?php

namespace Tests\Feature\Security;

use App\Models\AdministrativeProcess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdministrativeProcessIndexPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_index_route_uses_view_permission_instead_of_fixed_roles(): void
    {
        $route = Route::getRoutes()
            ->getByName('backoffice.administrative-processes.index');

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

        $this->assertContains(
            'permission:administrative_processes.view',
            $middleware,
        );

        $this->assertContains('active.backoffice', $middleware);
        $this->assertContains('mfa.backoffice', $middleware);
        $this->assertContains('log.backoffice', $middleware);
    }

    public function test_custom_role_with_view_permission_can_access_index(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.administrative-processes.index'))
            ->assertOk();
    }

    public function test_custom_role_without_view_permission_is_forbidden(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.create',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.administrative-processes.index'))
            ->assertForbidden();
    }

    public function test_candidate_remains_forbidden_even_with_view_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'candidate',
            permission: 'administrative_processes.view',
        );

        $this->assertFalse(
            $user->can('viewAny', AdministrativeProcess::class),
        );

        $this->actingAs($user)
            ->get(route('backoffice.administrative-processes.index'))
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
            'name' => 'administrative_process_index_'.str()->random(8),
            'label' => 'Administrative process index test role',
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
}
