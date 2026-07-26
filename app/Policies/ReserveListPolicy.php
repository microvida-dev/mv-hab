<?php

namespace App\Policies;

use App\Models\ReserveList;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ReserveListPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, ReserveList $reserveList): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ReserveList $reserveList): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function viewBackoffice(User $user, ReserveList $reserveList): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function updateBackoffice(User $user, ReserveList $reserveList): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'update');
    }
}
