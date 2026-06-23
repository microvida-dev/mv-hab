<?php

namespace App\Policies;

use App\Models\ApplicationReport;
use App\Models\User;

class ApplicationReportPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, ApplicationReport $report): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('reports.create') || $user->hasPermission('reports.export'));
    }

    public function download(User $user, ApplicationReport $report): bool
    {
        return $this->view($user, $report) && ($user->hasPermission('reports.export') || $user->hasPermission('reports.view'));
    }
}
