<?php

namespace App\Services\Navigation;

use App\Models\NavigationRecentItem;
use App\Models\User;

class RecentItemsService
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /** @return array<int, NavigationRecentItem> */
    public function forUser(User $user, int $limit = 8): array
    {
        return NavigationRecentItem::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_visited_at')
            ->latest()
            ->get()
            ->filter(fn (NavigationRecentItem $recent): bool => $this->isVisible($user, $recent))
            ->take($limit)
            ->values()
            ->all();
    }

    public function recordWorkspaceVisit(User $user, string $workspaceKey): ?NavigationRecentItem
    {
        $workspace = $this->workspaces->authorizedWorkspace($user, $workspaceKey);

        if ($workspace === null) {
            return null;
        }

        /** @var NavigationRecentItem $recent */
        $recent = NavigationRecentItem::query()->firstOrNew([
            'user_id' => $user->id,
            'item_type' => 'workspace',
            'workspace_key' => $workspaceKey,
        ]);

        $recent->fill([
            'label' => (string) $workspace['title'],
            'route_name' => 'workspaces.show',
            'route_parameters' => ['workspace' => $workspaceKey],
            'metadata' => ['source' => 'workspace_dashboard'],
            'last_visited_at' => now(),
            'visits_count' => $recent->exists ? ((int) $recent->visits_count + 1) : 1,
        ]);
        $recent->save();

        return $recent;
    }

    private function isVisible(User $user, NavigationRecentItem $recent): bool
    {
        if ($recent->item_type === 'workspace' && is_string($recent->workspace_key)) {
            return $this->workspaces->authorizedWorkspace($user, $recent->workspace_key) !== null;
        }

        if (is_string($recent->route_name)) {
            return $this->workspaces->findVisibleItemByRoute($user, $recent->route_name) !== null;
        }

        return false;
    }
}
