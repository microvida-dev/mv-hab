<?php

namespace App\Policies;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Platform\PlatformOperatorScopeService;
use App\Services\Reporting\ReportPermissionService;

class ReportDefinitionPolicy
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

    public function view(User $user, ReportDefinition $report): bool
    {
        return $this->permissions->canViewReport($user, $report);
    }

    public function create(User $user): bool
    {
        return $this->permissions->canManage($user);
    }

    public function update(User $user, ReportDefinition $report): bool
    {
        return $this->permissions->canManage($user);
    }

    public function delete(User $user, ReportDefinition $report): bool
    {
        return $this->permissions->canManage($user);
    }

    public function run(User $user, ReportDefinition $report): bool
    {
        return $this->view($user, $report);
    }

    public function export(User $user, ReportDefinition $report): bool
    {
        return ! $user->hasRole('auditor')
            && $this->permissions->canExportDefinition($user, $report);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('report_definitions.create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('reports.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        ReportDefinition $report,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->permissions->canViewReport($user, $report);
    }

    public function updateBackoffice(
        User $user,
        ReportDefinition $report,
    ): bool {
        return $user->hasPermission('report_definitions.update')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function deleteBackoffice(
        User $user,
        ReportDefinition $report,
    ): bool {
        return $user->hasPermission('report_definitions.delete')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function runBackoffice(
        User $user,
        ReportDefinition $report,
    ): bool {
        return $user->hasPermission('reports.run')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user)
            && $this->permissions->canViewReport($user, $report);
    }
}
