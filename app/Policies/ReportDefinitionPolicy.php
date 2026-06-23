<?php

namespace App\Policies;

use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Reporting\ReportPermissionService;

class ReportDefinitionPolicy
{
    public function __construct(private readonly ReportPermissionService $permissions) {}

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
        return $this->view($user, $report) && $user->hasPermission('reports.export');
    }
}
