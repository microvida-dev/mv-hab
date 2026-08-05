<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActorProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\AccessMunicipalScopeService;
use App\Services\Platform\ActorProfileResolver;

class RoleAssignmentPolicy
{
    public function __construct(
        private readonly AccessMunicipalScopeService $municipalScope,
        private readonly ActorProfileResolver $profiles,
    ) {}

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
        if ($role->name === 'administrator' && ! in_array(
            $this->profiles->primary($user),
            [ActorProfile::PlatformAdministrator, ActorProfile::MunicipalAdministrator],
            true,
        )) {
            return false;
        }

        return $this->municipalScope->ownsRole($user, $role);
    }
}
