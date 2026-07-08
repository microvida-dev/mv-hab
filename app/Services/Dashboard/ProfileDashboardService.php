<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Navigation\FavoritesService;
use App\Services\Navigation\RecentItemsService;
use App\Services\Navigation\WorkspaceService;
use App\Services\Navigation\WorkspacePreferenceService;

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
        private readonly WorkspacePreferenceService $workspacePreferences,
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
            'workspace_intelligence' => $this->workspaceIntelligence($user),
            'search_groups' => $this->workspaces->searchGroups($user),
            'widgets' => $this->widgets->forUser($user),
            'metrics' => $this->metrics->forUser($user),
            'quick_actions' => $this->quickActions->forUser($user),
            'deadlines' => $this->deadlines->forUser($user),
            'notifications_summary' => $this->notificationsSummary(),
            'workspace_preferences' => $this->workspacePreferences->payloadFor($user),
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

    /**
     * @return array<string, mixed>
     */
    private function workspaceIntelligence(User $user): array
    {
        $workspaces = collect($this->workspaces->availableFor($user));
        $favorites = collect($this->favorites->forUser($user));
        $recentItems = collect($this->recentItems->forUser($user));
        $preferences = $this->workspacePreferences->payloadFor($user);

        $preferredKey = $preferences['preferred_workspace'] ?? null;

        $preferred = is_string($preferredKey)
            ? $workspaces->firstWhere('key', $preferredKey)
            : null;

        $preferred ??= $workspaces->first();

        return [
            'preferred_key' => $preferred['key'] ?? null,
            'preferred' => $preferred ? $this->workspaceCardPayload($preferred, $favorites, $recentItems, true) : null,
            'workspaces' => $workspaces
                ->map(fn (array $workspace): array => $this->workspaceCardPayload(
                    $workspace,
                    $favorites,
                    $recentItems,
                    ($preferred['key'] ?? null) === ($workspace['key'] ?? null),
                ))
                ->values()
                ->all(),
            'summary' => [
                'workspaces' => $workspaces->count(),
                'favorites' => $favorites->count(),
                'recent_items' => $recentItems->count(),
                'preferred_label' => $preferred['title'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @param  \Illuminate\Support\Collection<int, mixed>  $favorites
     * @param  \Illuminate\Support\Collection<int, mixed>  $recentItems
     * @return array<string, mixed>
     */
    private function workspaceCardPayload(array $workspace, $favorites, $recentItems, bool $preferred): array
    {
        $key = (string) ($workspace['key'] ?? '');

        $workspaceFavorites = $favorites->filter(
            fn (mixed $favorite): bool => data_get($favorite, 'workspace_key') === $key
        );

        $workspaceRecentItems = $recentItems->filter(
            fn (mixed $item): bool => data_get($item, 'workspace_key') === $key
        );

        return [
            'key' => $key,
            'title' => (string) ($workspace['title'] ?? 'Workspace'),
            'description' => (string) ($workspace['description'] ?? ''),
            'icon' => (string) ($workspace['icon'] ?? 'dashboard'),
            'href' => route('workspaces.show', $key),
            'is_preferred' => $preferred,
            'favorites_count' => $workspaceFavorites->count(),
            'recent_count' => $workspaceRecentItems->count(),
            'modules_count' => collect($workspace['groups'] ?? [])
                ->flatMap(fn (array $group): array => $group['items'] ?? [])
                ->count(),
        ];
    }
}
