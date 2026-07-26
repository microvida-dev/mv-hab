<?php

namespace App\Policies;

use App\Models\ReportRun;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Reporting\ReportPermissionService;

class ReportRunPolicy
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportRun $run): bool
    {
        return $this->permissions->canViewReport($user, $run->definition)
            && $this->municipalScope->ownsReportRun($user, $run);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('reports.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, ReportRun $run): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->permissions->canViewReport($user, $run->definition)
            && $this->municipalScope->ownsReportRun($user, $run);
    }
}
