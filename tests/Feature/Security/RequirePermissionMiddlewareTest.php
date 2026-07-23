<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequirePermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth', 'permission:applications.view'])
            ->get('/_test/access/applications', fn () => response('allowed'));

        Route::middleware([
            'web',
            'auth',
            'permission:applications.view,documents.view',
        ])->get('/_test/access/application-or-document', fn () => response('allowed'));
    }

    public function test_guest_is_redirected_by_auth_before_permission_check(): void
    {
        $this->get('/_test/access/applications')
            ->assertRedirect();
    }

    public function test_authenticated_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/applications')
            ->assertForbidden();
    }

    public function test_authenticated_user_with_exact_permission_is_allowed(): void
    {
        $user = $this->userWithPermissions([
            'applications.view',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/applications')
            ->assertOk()
            ->assertSeeText('allowed');
    }

    public function test_module_wildcard_permission_is_allowed(): void
    {
        $user = $this->userWithPermissions([
            'applications.*',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/applications')
            ->assertOk();
    }

    public function test_global_wildcard_permission_is_allowed(): void
    {
        $user = $this->userWithPermissions([
            '*',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/applications')
            ->assertOk();
    }

    public function test_multiple_middleware_arguments_use_or_semantics(): void
    {
        $user = $this->userWithPermissions([
            'documents.view',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/application-or-document')
            ->assertOk();
    }

    public function test_user_without_any_requested_permission_is_forbidden(): void
    {
        $user = $this->userWithPermissions([
            'reports.view',
        ]);

        $this->actingAs($user)
            ->get('/_test/access/application-or-document')
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'test_permission_role_'.str()->random(8),
            'label' => 'Test permission role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): int {
                [$module, $action] = str_contains($permission, '.')
                    ? explode('.', $permission, 2)
                    : [$permission, $permission];

                return Permission::query()->firstOrCreate(
                    ['name' => $permission],
                    [
                        'module' => $module,
                        'action' => $action,
                        'description' => 'Permission used by middleware characterization tests.',
                    ],
                )->getKey();
            });

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
