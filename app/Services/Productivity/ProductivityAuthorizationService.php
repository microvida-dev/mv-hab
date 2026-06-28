<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Navigation\WorkspaceService;
use Illuminate\Support\Facades\Route;

class ProductivityAuthorizationService
{
    public function __construct(
        private readonly DashboardAuthorizationService $dashboardAuthorization,
        private readonly WorkspaceService $workspaces,
    ) {}

    public function canUseBackofficeProductivity(User $user): bool
    {
        return $this->dashboardAuthorization->isActive($user)
            && ! $user->hasRole('candidate')
            && (
                $this->dashboardAuthorization->hasPermission($user, 'work_tasks.view')
                || $this->dashboardAuthorization->hasPermission($user, 'work_tasks.view_team')
                || $this->dashboardAuthorization->hasAnyRole($user, ['administrator', 'auditor'])
            );
    }

    public function canSeeRoute(User $user, string $routeName, ?string $permission = null): bool
    {
        return $this->workspaces->canAccessItem($user, array_filter([
            'route' => $routeName,
            'permission' => $permission,
        ], fn (mixed $value): bool => $value !== null));
    }

    public function routeUrl(string $routeName, mixed $parameters = []): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName, $parameters);
    }

    public function canSeeWorkload(User $user): bool
    {
        return $this->dashboardAuthorization->hasAnyRole($user, ['administrator', 'auditor'])
            || $this->dashboardAuthorization->hasPermission($user, 'work_tasks.assign')
            || $this->dashboardAuthorization->hasPermission($user, 'work_tasks.view_team');
    }

    public function canSeeNotifications(User $user): bool
    {
        return $this->dashboardAuthorization->hasAnyRole($user, ['administrator', 'auditor'])
            || $this->dashboardAuthorization->hasPermission($user, 'notifications.view');
    }

    public function isReadOnly(User $user): bool
    {
        return $user->hasRole('auditor') && ! $user->hasRole('administrator');
    }
}
