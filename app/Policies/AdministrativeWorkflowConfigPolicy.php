<?php

namespace App\Policies;

use App\Models\AdministrativeWorkflowConfig;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AdministrativeWorkflowConfigPolicy
{
    use ChecksPermissions;

    private const MODULE = 'settings';

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator') && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, AdministrativeWorkflowConfig $administrativeWorkflowConfig): bool
    {
        return $user->hasRole('administrator') && $this->canAccess($user, self::MODULE, 'update');
    }
}
