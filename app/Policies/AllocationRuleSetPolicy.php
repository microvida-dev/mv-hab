<?php

namespace App\Policies;

use App\Models\AllocationRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class AllocationRuleSetPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, AllocationRuleSet $ruleSet): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function approve(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }
}
