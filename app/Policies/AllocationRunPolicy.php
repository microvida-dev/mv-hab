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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function viewBackoffice(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function updateBackoffice(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'update');
    }

    public function approveBackoffice(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'approve');
    }

    public function rejectBackoffice(User $user, AllocationRun $run): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'reject');
    }

    public function audit(User $user, AllocationRun $run): bool
    {
        return $this->canAccess($user, 'allocations', 'audit') || $user->hasRole('auditor');
    }

    public function auditBackoffice(User $user, AllocationRun $run): bool
    {
        return $this->canAccess($user, 'allocations', 'audit')
            || $user->hasRole('auditor');
    }
}
