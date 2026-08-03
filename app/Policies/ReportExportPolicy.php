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

    public function createTemporal(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->permissions->canAccessApplicationExportCatalog($user);
    }

    public function view(User $user, ReportExport $export): bool
    {
        $definition = $export->run->definition;
        if ($export->isTemporalApplicationResultExport() && $user->hasRole('auditor')) {
            return $user->hasPermission('reports.view')
                && $this->permissions->canAudit($user)
                && $this->municipalScope->ownsReportExport($user, $export);
        }

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
            && (
                ! $export->isTemporalApplicationResultExport()
                || (
                    ! $export->sensitive_fields_included
                    && ! $export->document_files_requested
                )
                || $user->hasPermission('reports.export_sensitive')
            )
            && $this->municipalScope->ownsReportExport($user, $export);
    }
}
