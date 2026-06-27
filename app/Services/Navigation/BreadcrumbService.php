<?php

namespace App\Services\Navigation;

use App\Models\User;

class BreadcrumbService
{
    public function __construct(
        private readonly WorkspaceResolver $resolver,
        private readonly WorkspaceService $workspaces,
    ) {}

    /**
     * @return list<array{label: string, route: string|null, parameters: array<string, mixed>}>
     */
    public function forRoute(?User $user, ?string $routeName, ?string $workspaceKey = null): array
    {
        if (! $user instanceof User) {
            return [];
        }

        $breadcrumbs = [
            [
                'label' => 'Painel Principal',
                'route' => 'dashboard',
                'parameters' => [],
            ],
        ];

        $workspace = $this->resolver->resolve($user, $routeName, $workspaceKey);
        if ($workspace === null) {
            return $breadcrumbs;
        }

        $breadcrumbs[] = [
            'label' => (string) $workspace['title'],
            'route' => 'workspaces.show',
            'parameters' => ['workspace' => $workspace['key']],
        ];

        if ($routeName !== null && $routeName !== 'workspaces.show') {
            $item = $this->workspaces->findVisibleItemByRoute($user, $routeName);

            if (is_array($item)) {
                $breadcrumbs[] = [
                    'label' => (string) $item['label'],
                    'route' => null,
                    'parameters' => [],
                ];
            } elseif (str_starts_with($routeName, 'backoffice.cases.applications.')) {
                $breadcrumbs[] = [
                    'label' => 'Case Workspace',
                    'route' => null,
                    'parameters' => [],
                ];
            }
        }

        return $breadcrumbs;
    }
}
