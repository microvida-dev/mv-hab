<?php

namespace Tests\Concerns;

use App\Models\MfaDevice;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;

trait CreatesPlatformOperatorFixtures
{
    /**
     * @param  list<string>  $permissions
     */
    protected function platformUser(
        array $permissions,
        bool $assigned = true,
        ?int $municipalityId = null,
    ): User {
        $user = User::factory()->create([
            'municipality_id' => $municipalityId,
            'status' => 'active',
            'mfa_required' => true,
        ]);

        $this->attachExactPermissions($user, $permissions);
        MfaDevice::factory()->confirmed()->for($user)->create();

        if ($assigned) {
            PlatformOperatorAssignment::factory()->for($user)->create();
        }

        return $user->refresh();
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function attachExactPermissions(User $user, array $permissions): Role
    {
        $role = Role::query()->create([
            'name' => 'platform_test_'.$user->id.'_'.Role::query()->count(),
            'label' => 'Perfil explícito de teste',
            'scope' => 'system',
            'is_system' => false,
            'is_active' => true,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(
            count($permissions),
            $permissionIds,
            'A fixture pediu uma permission que não existe no catálogo.',
        );

        $role->permissions()->sync($permissionIds->all());
        $user->roles()->attach($role);

        return $role;
    }
}
