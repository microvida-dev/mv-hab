<?php

namespace App\Policies;

use App\Models\DashboardWidget;
use App\Models\User;

class DashboardWidgetPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.view');
    }

    public function view(User $user, DashboardWidget $widget): bool
    {
        return $user->can('view', $widget->dashboard);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function update(User $user, DashboardWidget $widget): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function delete(User $user, DashboardWidget $widget): bool
    {
        return $user->hasPermission('reports.manage');
    }
}
