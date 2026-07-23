<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;

class RolePolicy
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canRead($user, 'roles.view') && $user->municipality_id !== null;
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user) && $this->municipalScope->ownsRole($user, $role);
    }

    public function create(User $user): bool
    {
        return $this->canMutate($user, 'roles.create') && $user->municipality_id !== null;
    }

    public function duplicate(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.create')
            && $this->municipalScope->ownsRole($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.update')
            && $this->municipalScope->ownsMutableRole($user, $role);
    }

    public function toggle(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->canMutate($user, 'roles.delete')
            && $this->municipalScope->ownsMutableRole($user, $role);
    }

    public function viewUsers(User $user, Role $role): bool
    {
        return $this->view($user, $role);
    }

    public function audit(User $user, Role $role): bool
    {
        return $this->canRead($user, 'roles.audit')
            && $this->municipalScope->ownsRole($user, $role);
    }

    private function canRead(User $user, string $permission): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission($permission);
    }

    private function canMutate(User $user, string $permission): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $user->hasPermission($permission);
    }
}
