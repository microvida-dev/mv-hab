<?php

namespace App\Services\Navigation;

use App\Models\User;

class WorkspaceResolver
{
    public function __construct(private readonly WorkspaceService $workspaces) {}

    /**
     * @return array<string, mixed>|null
     */
    public function resolve(User $user, ?string $routeName, ?string $workspaceKey = null): ?array
    {
        if ($workspaceKey !== null) {
            return $this->workspaces->authorizedWorkspace($user, $workspaceKey);
        }

        if ($routeName === null) {
            return null;
        }

        $item = $this->workspaces->findVisibleItemByRoute($user, $routeName);

        if (! is_array($item)) {
            return null;
        }

        $key = $item['workspace_key'] ?? null;

        return is_string($key) ? $this->workspaces->authorizedWorkspace($user, $key) : null;
    }
}
