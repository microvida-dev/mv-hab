<?php

namespace App\Services\Navigation;

use App\Models\NavigationFavorite;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FavoritesService
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /** @return array<int, NavigationFavorite> */
    public function forUser(User $user, int $limit = 6): array
    {
        return NavigationFavorite::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->filter(fn (NavigationFavorite $favorite): bool => $this->isVisible($user, $favorite))
            ->take($limit)
            ->values()
            ->all();
    }

    public function favoriteWorkspace(User $user, string $workspaceKey): NavigationFavorite
    {
        $workspace = $this->workspaces->authorizedWorkspace($user, $workspaceKey);

        if ($workspace === null) {
            throw new AuthorizationException('Workspace não autorizado.');
        }

        /** @var NavigationFavorite $favorite */
        $favorite = NavigationFavorite::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'item_type' => 'workspace',
                'workspace_key' => $workspaceKey,
            ],
            [
                'label' => (string) $workspace['title'],
                'route_name' => 'workspaces.show',
                'route_parameters' => ['workspace' => $workspaceKey],
                'metadata' => ['source' => 'workspace_dashboard'],
            ],
        );

        return $favorite;
    }

    public function remove(User $user, NavigationFavorite $favorite): void
    {
        if ((int) $favorite->user_id !== (int) $user->id) {
            throw new AuthorizationException('Favorito não autorizado.');
        }

        $favorite->delete();
    }

    private function isVisible(User $user, NavigationFavorite $favorite): bool
    {
        if ($favorite->item_type === 'workspace' && is_string($favorite->workspace_key)) {
            return $this->workspaces->authorizedWorkspace($user, $favorite->workspace_key) !== null;
        }

        if (is_string($favorite->route_name)) {
            return $this->workspaces->findVisibleItemByRoute($user, $favorite->route_name) !== null;
        }

        return false;
    }
}
