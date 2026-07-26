<?php

namespace Tests\Feature\Security;

use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleManagementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomRoleEffectivePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_inactive_role_stops_granting_permissions_and_reactivation_restores_them(): void
    {
        $administrator = $this->administrator();
        $permission = Permission::query()->where('name', 'applications.view')->firstOrFail();
        $role = $this->customRole('consulta_candidaturas', [$permission->id]);
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('applications.view'));

        app(RoleManagementService::class)->deactivate(
            $administrator,
            $role,
            'Suspensão temporária do perfil.',
        );

        $this->assertFalse($user->hasPermission('applications.view'));
        $this->assertFalse($user->hasRole('consulta_candidaturas'));

        app(RoleManagementService::class)->activate(
            $administrator,
            $role,
            'Reativação do perfil municipal.',
        );

        $this->assertTrue($user->hasPermission('applications.view'));
        $this->assertTrue($user->hasRole('consulta_candidaturas'));
    }

    public function test_inactive_role_does_not_remove_permission_granted_by_another_active_role(): void
    {
        $permission = Permission::query()->where('name', 'applications.view')->firstOrFail();
        $inactive = $this->customRole('consulta_inativa', [$permission->id], false);
        $active = $this->customRole('consulta_ativa', [$permission->id]);
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach([$inactive->id, $active->id]);

        $this->assertTrue($user->hasPermission('applications.view'));

        $active->forceFill(['is_active' => false])->save();

        $this->assertFalse($user->hasPermission('applications.view'));
    }

    public function test_wildcards_continue_to_work_only_through_active_roles(): void
    {
        $wildcard = Permission::query()->where('name', '*')->firstOrFail();
        $role = $this->customRole('acesso_global_controlado', [$wildcard->id]);
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('finance.approve'));

        $role->forceFill(['is_active' => false])->save();

        $this->assertFalse($user->hasPermission('finance.approve'));
    }

    /** @param list<int> $permissionIds */
    private function customRole(string $name, array $permissionIds, bool $active = true): Role
    {
        $role = Role::query()->create([
            'municipality_id' => Municipality::query()->value('id')
                ?? Municipality::factory()->create()->id,
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $active,
        ]);
        $role->permissions()->sync($permissionIds);

        return $role;
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
