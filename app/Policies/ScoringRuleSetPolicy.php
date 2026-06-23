<?php

namespace App\Policies;

use App\Models\ScoringRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ScoringRuleSetPolicy
{
    use ChecksPermissions;

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
}
