<?php

namespace App\Policies;

use App\Models\AllocationReport;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AllocationReportPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, AllocationReport $report): bool
    {
        return $this->viewAny($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view');
    }

    public function viewBackoffice(User $user, AllocationReport $report): bool
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

    public function approve(User $user, AllocationReport $report): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }

    public function approveBackoffice(User $user, AllocationReport $report): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'approve');
    }

    public function exportBackoffice(User $user, AllocationReport $report): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'export');
    }
}
