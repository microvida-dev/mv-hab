<?php

namespace App\Policies;

use App\Models\DashboardDefinition;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Reporting\ReportPermissionService;

class DashboardDefinitionPolicy
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly PlatformOperatorScopeService $platformScope,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, DashboardDefinition $dashboard): bool
    {
        return $this->permissions->canViewDashboard($user, $dashboard);
    }

    public function create(User $user): bool
    {
        return $this->permissions->canManage($user);
    }

    public function update(User $user, DashboardDefinition $dashboard): bool
    {
        return $this->permissions->canManage($user);
    }

    public function delete(User $user, DashboardDefinition $dashboard): bool
    {
        return $this->permissions->canManage($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('dashboard_definitions.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('dashboard_definitions.create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        DashboardDefinition $dashboard,
    ): bool {
        return $user->hasPermission('dashboard_definitions.update')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function deleteBackoffice(
        User $user,
        DashboardDefinition $dashboard,
    ): bool {
        return $user->hasPermission('dashboard_definitions.delete')
            && $this->platformScope->hasGlobalScope($user);
    }
}
