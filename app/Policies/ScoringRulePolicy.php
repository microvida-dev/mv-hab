<?php

namespace App\Policies;

use App\Models\ScoringCriterion;
use App\Models\ScoringRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ScoringRulePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, ScoringRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, ?ScoringCriterion $criterion = null): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function update(User $user, ScoringRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function delete(User $user, ScoringRule $rule): bool
    {
        return $this->update($user, $rule);
    }
}
