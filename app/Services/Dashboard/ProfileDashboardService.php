<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Navigation\FavoritesService;
use App\Services\Navigation\RecentItemsService;
use App\Services\Navigation\WorkspaceService;

class ProfileDashboardService
{
    public function __construct(
        private readonly DashboardAuthorizationService $authorization,
        private readonly DashboardWidgetRegistry $widgets,
        private readonly DashboardMetricService $metrics,
        private readonly DashboardQuickActionService $quickActions,
        private readonly DashboardDeadlineService $deadlines,
        private readonly WorkspaceService $workspaces,
        private readonly FavoritesService $favorites,
        private readonly RecentItemsService $recentItems,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $user->loadMissing(['roles', 'municipalTeams']);

        return [
            'greeting' => $this->greeting($user),
            'profile_label' => $this->authorization->profileLabel($user),
            'profile_keys' => $this->authorization->profileKeys($user),
            'team_names' => $this->teamNames($user),
            'workspaces' => $this->workspaces->availableFor($user),
            'favorites' => $this->favorites->forUser($user),
            'recent_items' => $this->recentItems->forUser($user),
            'search_groups' => $this->workspaces->searchGroups($user),
            'widgets' => $this->widgets->forUser($user),
            'metrics' => $this->metrics->forUser($user),
            'quick_actions' => $this->quickActions->forUser($user),
            'deadlines' => $this->deadlines->forUser($user),
            'notifications_summary' => $this->notificationsSummary(),
        ];
    }

    private function greeting(User $user): string
    {
        $firstName = trim(explode(' ', trim($user->name))[0]);
        $name = $firstName !== '' ? $firstName : 'utilizador';

        return 'Bom trabalho, '.$name;
    }

    /**
     * @return array<int, string>
     */
    private function teamNames(User $user): array
    {
        return $user->municipalTeams()
            ->wherePivotNull('left_at')
            ->orderBy('municipal_teams.name')
            ->pluck('municipal_teams.name')
            ->filter(fn (mixed $name): bool => is_string($name))
            ->values()
            ->all();
    }

    /**
     * @return array{label: string, description: string}
     */
    private function notificationsSummary(): array
    {
        return [
            'label' => 'Notificações operacionais',
            'description' => 'As notificações continuam ligadas aos módulos existentes e são apresentadas aqui como ponto global de atenção.',
        ];
    }
}
