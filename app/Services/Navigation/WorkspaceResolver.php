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

        if (str_starts_with($routeName, 'backoffice.cases.applications.')
            || str_starts_with($routeName, 'backoffice.cases.complaints.')
            || str_starts_with($routeName, 'backoffice.cases.tickets.')) {
            return $this->workspaces->authorizedWorkspace($user, 'atendimento');
        }

        if (str_starts_with($routeName, 'backoffice.cases.contests.')) {
            return $this->workspaces->authorizedWorkspace($user, 'concursos');
        }

        if (str_starts_with($routeName, 'backoffice.cases.contracts.')
            || str_starts_with($routeName, 'backoffice.cases.maintenance.')
            || str_starts_with($routeName, 'backoffice.cases.inspections.')
            || str_starts_with($routeName, 'backoffice.cases.housing-units.')) {
            return $this->workspaces->authorizedWorkspace($user, 'patrimonio');
        }

        if (str_starts_with($routeName, 'backoffice.cases.documents.')
            || str_starts_with($routeName, 'backoffice.cases.rgpd.')
            || str_starts_with($routeName, 'backoffice.cases.audit.')) {
            return $this->workspaces->authorizedWorkspace($user, 'gestao');
        }

        $item = $this->workspaces->findVisibleItemByRoute($user, $routeName);

        if (! is_array($item)) {
            return null;
        }

        $key = $item['workspace_key'] ?? null;

        return is_string($key) ? $this->workspaces->authorizedWorkspace($user, $key) : null;
    }
}
