<?php

namespace App\Policies;

use App\Models\EligibilityRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class EligibilityRuleSetPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'eligibility', 'view');
    }

    public function view(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'create');
    }

    public function update(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'update');
    }

    public function activate(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'approve');
    }

    public function archive(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return $this->update($user, $ruleSet);
    }

    public function duplicate(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return $this->create($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'eligibility', 'view');
    }

    public function viewBackoffice(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsEligibilityRuleSet($user, $ruleSet);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'eligibility', 'create');
    }

    public function updateBackoffice(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'update')
            && $this->municipalScope->ownsEligibilityRuleSet($user, $ruleSet);
    }

    public function activateBackoffice(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'activate')
            && $this->municipalScope->ownsEligibilityRuleSet($user, $ruleSet);
    }

    public function archiveBackoffice(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'archive')
            && $this->municipalScope->ownsEligibilityRuleSet($user, $ruleSet);
    }

    public function duplicateBackoffice(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'duplicate')
            && $this->municipalScope->ownsEligibilityRuleSet($user, $ruleSet);
    }
}
