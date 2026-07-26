<?php

namespace App\Policies;

use App\Models\DashboardWidget;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;

class DashboardWidgetPolicy
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

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

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('dashboard_widgets.create')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        DashboardWidget $widget,
    ): bool {
        return $user->hasPermission('dashboard_widgets.update')
            && $this->platformScope->hasGlobalScope($user);
    }

    public function deleteBackoffice(
        User $user,
        DashboardWidget $widget,
    ): bool {
        return $user->hasPermission('dashboard_widgets.delete')
            && $this->platformScope->hasGlobalScope($user);
    }
}
