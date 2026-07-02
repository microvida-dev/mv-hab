<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Dashboard\Operations\OperationsSummaryProvider;
use App\Services\Dashboard\Operations\TodayProvider;
use App\Services\Productivity\ProductivityDashboardService;

class MunicipalOperationsDashboardService
{
    public function __construct(
        private readonly ProfileDashboardService $profileDashboards,
        private readonly ProductivityDashboardService $productivityDashboards,
        private readonly OperationsSummaryProvider $summaryProvider,
        private readonly TodayProvider $todayProvider,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $dashboard = $this->profileDashboards->forUser($user);
        $productivity = $this->productivityDashboards->forUser($user);

        return [
            'dashboard' => $dashboard,
            'productivity' => $productivity,
            'workspaces' => $dashboard['workspaces'] ?? [],
            'favorites' => $dashboard['favorites'] ?? [],
            'recentItems' => $dashboard['recent_items'] ?? [],
            'quickActions' => $dashboard['quick_actions'] ?? [],
            'searchGroups' => $dashboard['search_groups'] ?? [],
            'operationsSummary' => $this->summaryProvider->forUser($user, $dashboard),
            'todayOperations' => $this->todayProvider->forUser($user, $dashboard),
        ];
    }
}
