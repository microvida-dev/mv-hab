<?php

namespace App\Policies;

use App\Models\ScoringRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ScoringRuleSetPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ScoringRuleSet $ruleSet): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function update(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function activate(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }

    public function archive(User $user, ScoringRuleSet $ruleSet): bool
    {
        return $this->update($user, $ruleSet);
    }

    public function duplicate(User $user, ScoringRuleSet $ruleSet): bool
    {
        return $this->create($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'view');
    }

    public function viewBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function updateBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function activateBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'activate')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function archiveBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'archive')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function duplicateBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'duplicate')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }
}
