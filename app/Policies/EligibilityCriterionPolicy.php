<?php

namespace App\Policies;

use App\Models\EligibilityCriterion;
use App\Models\EligibilityRuleSet;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class EligibilityCriterionPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'eligibility', 'view');
    }

    public function view(User $user, EligibilityCriterion $criterion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, EligibilityRuleSet $ruleSet): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'create');
    }

    public function update(User $user, EligibilityCriterion $criterion): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'eligibility', 'update');
    }

    public function activate(User $user, EligibilityCriterion $criterion): bool
    {
        return $this->update($user, $criterion);
    }
}
