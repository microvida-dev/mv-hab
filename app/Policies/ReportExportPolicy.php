<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\ReportPermissionService;

class ReportExportPolicy
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportExport $export): bool
    {
        $definition = $export->run->definition;
        $canView = $this->permissions->isApplicationReport($definition)
            ? $this->permissions->canExportDefinition($user, $definition)
            : $this->permissions->canViewReport($user, $definition);

        return $canView
            && $this->municipalScope->ownsReportExport($user, $export);
    }

    public function download(User $user, ReportExport $export): bool
    {
        return ! $user->hasRole('auditor')
            && $this->permissions->canExport($user, $export->run->definition, $export->scope)
            && $this->municipalScope->ownsReportExport($user, $export);
    }
}
