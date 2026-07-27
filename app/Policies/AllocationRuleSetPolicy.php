<?php

namespace App\Policies;

use App\Models\AllocationRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AllocationRuleSetPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, AllocationRuleSet $ruleSet): bool
    {
        return $this->viewAny($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'allocations', 'view')
            && $this->municipalScope->ownsAllocationRuleSet($user, $ruleSet);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function update(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function updateBackoffice(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'update')
            && $this->municipalScope->ownsAllocationRuleSet($user, $ruleSet);
    }

    public function approve(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }

    public function approveBackoffice(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'approve')
            && $this->municipalScope->ownsAllocationRuleSet($user, $ruleSet);
    }

    public function duplicateBackoffice(User $user, AllocationRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'allocations', 'create')
            && $this->municipalScope->ownsAllocationRuleSet($user, $ruleSet);
    }
}
