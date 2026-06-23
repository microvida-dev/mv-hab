<?php

namespace App\Policies;

use App\Models\ScoringRuleSet;
use App\Models\TieBreakerRule;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class TieBreakerRulePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, TieBreakerRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, ?ScoringRuleSet $ruleSet = null): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'create');
    }

    public function update(User $user, TieBreakerRule $rule): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function activate(User $user, TieBreakerRule $rule): bool
    {
        return $this->update($user, $rule);
    }
}
