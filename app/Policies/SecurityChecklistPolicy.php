<?php

namespace App\Policies;

use App\Models\SecurityChecklist;
use App\Models\User;
use App\Services\Security\SecurityMunicipalScopeService;

class SecurityChecklistPolicy
{
    public function __construct(
        private readonly SecurityMunicipalScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.view');
    }

    public function view(User $user, SecurityChecklist $checklist): bool
    {
        return $this->viewAny($user)
            && $this->scope->ownsChecklist($user, $checklist);
    }

    public function create(User $user): bool
    {
        return $user->municipality_id !== null
            && $user->hasPermission('security.update');
    }

    public function update(User $user, SecurityChecklist $checklist): bool
    {
        return $user->hasPermission('security.update')
            && $this->scope->ownsChecklist($user, $checklist);
    }

    public function approve(User $user, SecurityChecklist $checklist): bool
    {
        return $user->hasPermission('security.approve')
            && $this->scope->ownsChecklist($user, $checklist);
    }
}
