<?php

namespace Tests\Feature\Security;

use App\Models\AdministrativeProcess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrativeProcessBackofficePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_custom_role_with_view_permission_can_view_backoffice_process(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->assertTrue(
            $user->can('viewBackoffice', $process),
        );
    }

    public function test_custom_role_without_view_permission_cannot_view_backoffice_process(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.create',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->assertFalse(
            $user->can('viewBackoffice', $process),
        );
    }

    public function test_candidate_with_view_permission_cannot_view_backoffice_process(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'administrative_processes.view',
            ],
        );

        $process = AdministrativeProcess::factory()->create();

        $this->assertFalse(
            $user->can('viewBackoffice', $process),
        );
    }

    public function test_custom_role_with_audit_permission_can_audit_backoffice_process(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.audit',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->assertTrue(
            $user->can('auditBackoffice', $process),
        );
    }

    public function test_custom_role_with_view_permission_can_audit_backoffice_process(): void
    {
        $user = $this->userWithCustomRole([
            'administrative_processes.view',
        ]);

        $process = AdministrativeProcess::factory()->create();

        $this->assertTrue(
            $user->can('auditBackoffice', $process),
        );
    }

    public function test_candidate_with_view_and_audit_permissions_cannot_audit_backoffice_process(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'administrative_processes.view',
                'administrative_processes.audit',
            ],
        );

        $process = AdministrativeProcess::factory()->create();

        $this->assertFalse(
            $user->can('auditBackoffice', $process),
        );
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
            'name' => 'administrative_process_backoffice_'.str()->random(8),
            'label' => 'Administrative process backoffice test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(
            count($permissions),
            $permissionIds,
        );

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

        $this->assertCount(
            count($permissions),
            $permissionIds,
        );

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
