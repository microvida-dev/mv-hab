<?php

namespace App\Policies;

use App\Models\EligibilityRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class EligibilityRuleSetPolicy
{
    use ChecksPermissions;

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
}
