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
            ->orderBy('sort_order')
            ->orderBy('created_at')
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
        $favorite = NavigationFavorite::query()->firstOrNew([
            'user_id' => $user->id,
            'item_type' => 'workspace',
            'workspace_key' => $workspaceKey,
        ]);

        if (! $favorite->exists) {
            $favorite->sort_order = ((int) NavigationFavorite::query()
                ->where('user_id', $user->id)
                ->max('sort_order')) + 1;
        }

        $favorite->fill([
            'label' => (string) $workspace['title'],
            'route_name' => 'workspaces.show',
            'route_parameters' => ['workspace' => $workspaceKey],
            'metadata' => ['source' => 'workspace_dashboard'],
        ]);

        $favorite->save();

        return $favorite;
    }

    /**
     * @param  list<int>  $favoriteIds
     */
    public function reorder(User $user, array $favoriteIds): void
    {
        $favorites = NavigationFavorite::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $favoriteIds)
            ->get()
            ->keyBy('id');

        if ($favorites->count() !== count($favoriteIds)) {
            throw new AuthorizationException('Favoritos não autorizados.');
        }

        foreach ($favoriteIds as $index => $favoriteId) {
            $favorite = $favorites->get($favoriteId);

            if ($favorite instanceof NavigationFavorite) {
                $favorite->forceFill([
                    'sort_order' => $index + 1,
                ])->save();
            }
        }
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
