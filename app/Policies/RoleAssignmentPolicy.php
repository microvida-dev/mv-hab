<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;

class RoleAssignmentPolicy
{
    public function __construct(private readonly AccessMunicipalScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function assign(User $user, Role $role): bool
    {
        return $this->can($user, 'assign') && $role->isActive() && $this->withinScope($user, $role);
    }

    public function remove(User $user, Role $role): bool
    {
        return $this->can($user, 'remove') && $this->withinScope($user, $role);
    }

    private function can(User $user, string $action): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("roles.{$action}");
    }

    private function withinScope(User $user, Role $role): bool
    {
        if ($role->name === 'administrator') {
            return $user->hasRole('administrator')
                && $this->municipalScope->ownsRole($user, $role);
        }

        return $this->municipalScope->ownsRole($user, $role);
    }
}
