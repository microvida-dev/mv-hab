<?php

namespace App\Policies;

use App\Models\AdministrativeWorkflowConfig;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdministrativeWorkflowConfigPolicy
{
    use ChecksPermissions;

    private const MODULE = 'settings';

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator') && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, AdministrativeWorkflowConfig $administrativeWorkflowConfig): bool
    {
        return $user->hasRole('administrator') && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        AdministrativeWorkflowConfig $config,
    ): bool {
        return $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsAdministrativeWorkflowConfig(
                $user,
                $config,
            );
    }

    public function activateBackoffice(
        User $user,
        AdministrativeWorkflowConfig $config,
    ): bool {
        return $this->canAccess($user, self::MODULE, 'activate')
            && $this->municipalScope->ownsAdministrativeWorkflowConfig(
                $user,
                $config,
            );
    }

    public function deactivateBackoffice(
        User $user,
        AdministrativeWorkflowConfig $config,
    ): bool {
        return $this->canAccess($user, self::MODULE, 'deactivate')
            && $this->municipalScope->ownsAdministrativeWorkflowConfig(
                $user,
                $config,
            );
    }
}
