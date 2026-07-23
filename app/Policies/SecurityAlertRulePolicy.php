<?php

namespace App\Policies;

use App\Models\SecurityAlertRule;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class SecurityAlertRulePolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.view');
    }

    public function view(User $user, SecurityAlertRule $rule): bool
    {
        return $this->viewAny($user)
            && $this->scope->alertRules(
                SecurityAlertRule::query()->whereKey($rule),
                $user,
            )->exists();
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.update');
    }

    public function update(User $user, SecurityAlertRule $rule): bool
    {
        return $user->hasPermission('security.update')
            && $this->scope->ownsMutableAlertRule($user, $rule);
    }
}
