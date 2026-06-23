<?php

namespace App\Policies;

use App\Models\ScoringCriterion;
use App\Models\ScoringRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ScoringCriterionPolicy
{
    use ChecksPermissions;

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
}
