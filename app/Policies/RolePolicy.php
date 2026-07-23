<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canRead($user, 'roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user) && $this->withinManagedScope($role);
    }

    public function create(User $user): bool
    {
        return $this->canMutate($user, 'roles.create');
    }

    public function duplicate(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.create') && $this->withinManagedScope($role);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.update') && $role->isMunicipalCustom();
    }

    public function toggle(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.delete') && $role->isMunicipalCustom();
    }

    public function viewUsers(User $user, Role $role): bool
    {
        return $this->view($user, $role);
    }

    public function audit(User $user, Role $role): bool
    {
        return $this->canRead($user, 'roles.audit') && $this->withinManagedScope($role);
    }

    private function canRead(User $user, string $permission): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission($permission);
    }

    private function canMutate(User $user, string $permission): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermission($permission);
    }

    private function withinManagedScope(Role $role): bool
    {
        return $role->isSystem() || $role->scope === 'municipal';
    }
}
