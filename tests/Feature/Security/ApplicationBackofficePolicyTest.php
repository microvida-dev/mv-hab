<?php

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBackofficePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_custom_role_with_view_permission_can_list_backoffice_applications(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $this->assertTrue(
            $user->can('viewAnyBackoffice', Application::class),
        );
    }

    public function test_custom_role_without_view_permission_cannot_list_backoffice_applications(): void
    {
        $user = $this->userWithCustomRole([
            'applications.create',
        ]);

        $this->assertFalse(
            $user->can('viewAnyBackoffice', Application::class),
        );
    }

    public function test_candidate_cannot_list_backoffice_applications_even_with_view_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.view',
            ],
        );

        $this->assertFalse(
            $user->can('viewAnyBackoffice', Application::class),
        );
    }

    public function test_custom_role_with_view_permission_can_view_backoffice_application(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $application = Application::factory()->create();

        $this->assertTrue(
            $user->can('viewBackoffice', $application),
        );
    }

    public function test_candidate_cannot_view_backoffice_application_even_when_it_is_their_own(): void
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

        $this->assertFalse(
            $user->can('viewBackoffice', $application),
        );
    }

    public function test_custom_role_with_audit_permission_can_audit_backoffice_application(): void
    {
        $user = $this->userWithCustomRole([
            'applications.audit',
        ]);

        $application = Application::factory()->create();

        $this->assertTrue(
            $user->can('auditBackoffice', $application),
        );
    }

    public function test_custom_role_with_view_permission_can_audit_backoffice_application(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $application = Application::factory()->create();

        $this->assertTrue(
            $user->can('auditBackoffice', $application),
        );
    }

    public function test_candidate_cannot_audit_backoffice_application_even_with_permissions(): void
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

        $this->assertFalse(
            $user->can('auditBackoffice', $application),
        );
    }

    public function test_custom_role_with_update_permission_can_update_backoffice_application(): void
    {
        $user = $this->userWithCustomRole([
            'applications.update',
        ]);

        $application = Application::factory()->create();

        $this->assertTrue(
            $user->can('updateBackoffice', $application),
        );
    }

    public function test_candidate_cannot_update_backoffice_application_even_with_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'applications.update',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse(
            $user->can('updateBackoffice', $application),
        );
    }

    public function test_auditor_cannot_update_backoffice_application_even_with_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'auditor',
            permissions: [
                'applications.update',
            ],
        );

        $application = Application::factory()->create();

        $this->assertFalse(
            $user->can('updateBackoffice', $application),
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
            'name' => 'application_backoffice_'.str()->random(8),
            'label' => 'Application backoffice test role',
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
