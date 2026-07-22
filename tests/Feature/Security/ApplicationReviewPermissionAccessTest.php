<?php

namespace Tests\Feature\Security;

use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationReviewPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_review_routes_use_expected_permission_middleware(): void
    {
        $expected = [
            'backoffice.application-reviews.create'
                => 'permission:administrative_processes.create',
            'backoffice.application-reviews.store'
                => 'permission:administrative_processes.create',
            'backoffice.application-reviews.show'
                => 'permission:administrative_processes.view',
            'backoffice.application-reviews.complete'
                => 'permission:administrative_processes.update',
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
                "Route [{$routeName}] still effectively uses fixed role middleware.",
            );

            $this->assertContains($permissionMiddleware, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_create_permission_reaches_create_route(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.create',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.application-reviews.create', $process))
            ->assertOk();
    }

    public function test_custom_role_without_create_permission_is_forbidden(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.application-reviews.create', $process))
            ->assertForbidden();
    }

    public function test_custom_role_with_view_permission_can_view_review(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $review = ApplicationReview::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.application-reviews.show', $review))
            ->assertOk();
    }

    public function test_custom_role_without_view_permission_cannot_view_review(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.create',
        ]);

        $review = ApplicationReview::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.application-reviews.show', $review))
            ->assertForbidden();
    }

    public function test_candidate_remains_forbidden_by_policy_even_with_create_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'candidate',
            permission: 'administrative_processes.create',
        );

        $this->assertFalse(
            $user->can('create', ApplicationReview::class),
        );
    }

    public function test_auditor_remains_forbidden_by_policy_even_with_update_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermission(
            roleName: 'auditor',
            permission: 'administrative_processes.update',
        );

        $review = ApplicationReview::factory()->create();

        $this->assertFalse(
            $user->can('update', $review),
        );
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
            'name' => 'application_review_'.str()->random(8),
            'label' => 'Application review test role',
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
