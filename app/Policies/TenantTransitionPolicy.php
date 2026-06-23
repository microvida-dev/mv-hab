<?php

namespace App\Policies;

use App\Models\TenantTransition;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class TenantTransitionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, TenantTransition $transition): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }
}
