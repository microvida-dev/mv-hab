<?php

namespace App\Policies;

use App\Models\DashboardDefinition;
use App\Models\User;
use App\Services\Reporting\ReportPermissionService;

class DashboardDefinitionPolicy
{
    public function __construct(private readonly ReportPermissionService $permissions) {}

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
}
