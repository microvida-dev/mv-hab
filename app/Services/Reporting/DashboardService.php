<?php

namespace App\Services\Reporting;

use App\Enums\ReportAccessType;
use App\Models\DashboardDefinition;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DashboardService
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly IndicatorCalculationService $indicators,
        private readonly ReportAccessLogger $access,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function render(string $code, User $user, array $filters): array
    {
        $dashboard = DashboardDefinition::query()->where('code', $code)->where('is_active', true)->firstOrFail();
        if (! $this->permissions->canViewDashboard($user, $dashboard)) {
            throw new AuthorizationException;
        }

        $dashboard->load(['widgets' => fn ($query) => $query->where('is_active', true), 'widgets.indicator']);
        $widgets = $dashboard->widgets
            ->filter(fn ($widget) => ! $widget->required_permission || $user->hasPermission($widget->required_permission))
            ->map(fn ($widget) => [
                'widget' => $widget,
                'result' => $widget->indicator
                    ? $this->indicators->calculate($widget->indicator, $filters, $user)
                    : ['status' => 'unavailable', 'value' => null, 'calculated_at' => now()],
            ]);

        $this->access->record($user, ReportAccessType::ViewDashboard, dashboard: $dashboard, filters: $filters);

        return compact('dashboard', 'widgets');
    }
}
