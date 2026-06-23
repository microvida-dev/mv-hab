<?php

namespace App\Policies;

use App\Models\AllocationRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AllocationRunPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, AllocationRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function audit(User $user, AllocationRun $run): bool
    {
        return $this->canAccess($user, 'allocations', 'audit') || $user->hasRole('auditor');
    }
}
