<?php

namespace App\Services\Search;

use App\Models\User;
use App\Services\Navigation\WorkspaceService;

class SearchResultAuthorizationService
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /**
     * @param  list<string>|null  $roles
     */
    public function canAccess(User $user, string $routeName, ?string $permission = null, ?array $roles = null): bool
    {
        return $this->workspaces->canAccessItem($user, array_filter([
            'route' => $routeName,
            'permission' => $permission,
            'roles' => $roles,
        ], fn (mixed $value): bool => $value !== null));
    }
}
