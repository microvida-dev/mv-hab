<?php

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\Permission;
use App\Models\ProcessConfirmation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationProcessTrackingPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_process_tracking_routes_use_expected_permissions(): void
    {
        $expected = [
            'backoffice.applications.public-status.show'
                => 'permission:applications.view',

            'backoffice.applications.public-status.update'
                => 'permission:applications.update',

            'backoffice.applications.process-confirmations.generate'
                => 'permission:applications.update,applications.approve',

            'backoffice.process-confirmations.index'
                => 'permission:applications.view',

            'backoffice.process-confirmations.show'
                => 'permission:applications.view',

            'backoffice.process-confirmations.send'
                => 'permission:applications.update,applications.approve',
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

    public function test_custom_role_with_view_permission_can_access_public_status(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'backoffice.applications.public-status.show',
                $application,
            ))
            ->assertOk();
    }

    public function test_user_without_view_permission_cannot_access_public_status(): void
    {
        $user = $this->userWithCustomRole([
            'applications.create',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'backoffice.applications.public-status.show',
                $application,
            ))
            ->assertForbidden();
    }

    public function test_custom_role_with_update_permission_reaches_public_status_update(): void
    {
        $user = $this->userWithCustomRole([
            'applications.update',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->put(route(
                'backoffice.applications.public-status.update',
                $application,
            ));

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_candidate_cannot_access_backoffice_public_status_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
                'applications.update',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route(
                'backoffice.applications.public-status.show',
                $application,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route(
                'backoffice.applications.public-status.update',
                $application,
            ))
            ->assertForbidden();
    }

    public function test_custom_role_with_view_permission_can_access_confirmation_index(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.process-confirmations.index'))
            ->assertOk();
    }

    public function test_custom_role_with_view_permission_can_access_confirmation_show(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $confirmation = ProcessConfirmation::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'backoffice.process-confirmations.show',
                $confirmation,
            ))
            ->assertOk();
    }

    public function test_custom_role_with_update_permission_reaches_confirmation_generation(): void
    {
        $user = $this->userWithCustomRole([
            'applications.update',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.applications.process-confirmations.generate',
                    $application,
                ),
                [
                    'force_regenerate' => false,
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_custom_role_with_approve_permission_reaches_confirmation_send(): void
    {
        $user = $this->userWithCustomRole([
            'applications.approve',
        ]);

        $confirmation = ProcessConfirmation::factory()->create();

        $response = $this->actingAs($user)
            ->post(route(
                'backoffice.process-confirmations.send',
                $confirmation,
            ));

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_candidate_cannot_access_process_confirmations_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
                'applications.update',
                'applications.approve',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.process-confirmations.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.applications.process-confirmations.generate',
                    $application,
                ),
                [
                    'force_regenerate' => false,
                ],
            )
            ->assertForbidden();
    }

    public function test_verified_auditor_cannot_generate_confirmation_even_with_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'auditor',
            permissions: [
                'applications.update',
                'applications.approve',
            ],
        );

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified_at' => now(),
            ])
            ->post(
                route(
                    'backoffice.applications.process-confirmations.generate',
                    $application,
                ),
                [
                    'force_regenerate' => false,
                ],
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
            'name' => 'process_tracking_'.str()->random(8),
            'label' => 'Process tracking test role',
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
