<?php

namespace App\Policies;

use App\Models\IndicatorDefinition;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;

class IndicatorDefinitionPolicy
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, IndicatorDefinition $indicator): bool
    {
        return $this->viewAny($user) && (! $indicator->required_permission || $user->hasPermission($indicator->required_permission));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function update(User $user, IndicatorDefinition $indicator): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function delete(User $user, IndicatorDefinition $indicator): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('indicator_definitions.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        IndicatorDefinition $indicator,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && (
                ! $indicator->required_permission
                || $user->hasPermission($indicator->required_permission)
            );
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('indicator_definitions.create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        IndicatorDefinition $indicator,
    ): bool {
        return $user->hasPermission('indicator_definitions.update')
            && $this->platformScope->hasGlobalScope($user);
    }
}
