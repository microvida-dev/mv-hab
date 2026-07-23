<?php

namespace App\Services\Search;

use App\Enums\FeatureKey;
use App\Models\User;
use App\Services\Navigation\WorkspaceService;

class SearchResultAuthorizationService
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /**
     * @param  list<string>|null  $roles
     */
    public function canAccess(
        User $user,
        string $routeName,
        ?string $permission = null,
        ?array $roles = null,
        ?FeatureKey $feature = null,
    ): bool {
        return $this->workspaces->canAccessItem($user, array_filter([
            'route' => $routeName,
            'permission' => $permission,
            'roles' => $roles,
            'feature' => $feature,
        ], fn (mixed $value): bool => $value !== null));
    }
}
