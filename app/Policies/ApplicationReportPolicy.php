<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationReportPolicy
{
    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ApplicationReport $report): bool
    {
        return $this->viewAny($user)
            && $this->municipalScope->ownsApplicationReport($user, $report);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.create') || $user->hasPermission('reports.export'));
    }

    public function createForApplication(User $user, Application $application): bool
    {
        return $this->create($user)
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function download(User $user, ApplicationReport $report): bool
    {
        return $this->view($user, $report) && ($user->hasPermission('reports.export') || $user->hasPermission('reports.view'));
    }
}
