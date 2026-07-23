<?php

namespace App\Policies;

use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class SecurityAlertPolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.view');
    }

    public function view(User $user, SecurityAlert $alert): bool
    {
        return $this->viewAny($user)
            && $this->scope->ownsAlert($user, $alert);
    }

    public function update(User $user, SecurityAlert $alert): bool
    {
        return $user->hasPermission('security.update')
            && $this->scope->ownsAlert($user, $alert);
    }

    public function resolve(User $user, SecurityAlert $alert): bool
    {
        return $user->hasPermission('security.resolve')
            && $this->scope->ownsAlert($user, $alert);
    }
}
