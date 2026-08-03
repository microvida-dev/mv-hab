<?php

namespace App\Policies;

use App\Models\TemplateVariable;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Platform\PlatformOperatorScopeService;

class TemplateVariablePolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, TemplateVariable $variable): bool
    {
        return $this->canManageCommunications($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('communication_variables.view');
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('communication_variables.create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        TemplateVariable $variable,
    ): bool {
        return $user->hasPermission('communication_variables.update')
            && $this->platformScope->hasGlobalScope($user);
    }
}
