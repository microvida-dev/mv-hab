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

class AdministrativeProcessMutationPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_mutation_routes_use_update_permission_instead_of_fixed_roles(): void
    {
        $routeNames = [
            'backoffice.administrative-processes.assign',
            'backoffice.administrative-processes.start-preliminary-review',
            'backoffice.administrative-processes.start-document-review',
            'backoffice.administrative-processes.start-eligibility-review',
        ];

        foreach ($routeNames as $routeName) {
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

            $this->assertContains(
                'permission:administrative_processes.update',
                $middleware,
            );

            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_update_permission_reaches_assign_action(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.update',
        ]);

        $process = AdministrativeProcess::factory()->create();
        $assignee = User::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->post(
                route('backoffice.administrative-processes.assign', $process),
                ['assigned_to' => $assignee->id],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_user_without_update_permission_is_forbidden(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.administrative-processes.start-preliminary-review',
                    $process,
                ),
            )
            ->assertForbidden();
    }

    public function test_candidate_remains_forbidden_even_with_update_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'administrative_processes.update',
            ],
        );

        $process = AdministrativeProcess::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.administrative-processes.start-document-review',
                    $process,
                ),
            )
            ->assertForbidden();
    }

    public function test_auditor_remains_forbidden_even_with_update_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'auditor',
            permissions: [
                'administrative_processes.update',
            ],
        );

        $process = AdministrativeProcess::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified_at' => now(),
            ])
            ->post(
                route(
                    'backoffice.administrative-processes.start-eligibility-review',
                    $process,
                ),
            )
            ->assertForbidden();
    }

    /**
     * @param list<string> $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'administrative_process_mutation_'.str()->random(8),
            'label' => 'Administrative process mutation test role',
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
     * @param list<string> $permissions
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
