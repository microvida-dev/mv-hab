<?php

namespace App\Policies;

use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ScoringCriterionPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ScoringCriterion $criterion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, ?ScoringRuleSet $ruleSet = null): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function update(User $user, ScoringCriterion $criterion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function activate(User $user, ScoringCriterion $criterion): bool
    {
        return $this->update($user, $criterion);
    }

    public function createBackoffice(User $user, ScoringRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create')
            && $this->municipalScope->ownsScoringRuleSet($user, $ruleSet);
    }

    public function viewBackoffice(User $user, ScoringCriterion $criterion): bool
    {
        return $this->canAccess($user, 'scoring', 'view')
            && $this->municipalScope->ownsScoringCriterion($user, $criterion);
    }

    public function updateBackoffice(User $user, ScoringCriterion $criterion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update')
            && $this->municipalScope->ownsScoringCriterion($user, $criterion);
    }

    public function activateBackoffice(User $user, ScoringCriterion $criterion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'activate')
            && $this->municipalScope->ownsScoringCriterion($user, $criterion);
    }

    public function deactivateBackoffice(User $user, ScoringCriterion $criterion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'deactivate')
            && $this->municipalScope->ownsScoringCriterion($user, $criterion);
    }
}
