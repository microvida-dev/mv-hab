<?php

namespace App\Services\Search\Sources;

use App\Models\User;
use App\Services\Navigation\WorkspaceService;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;

class WorkspaceSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly WorkspaceService $workspaces) {}

    public function key(): string
    {
        return 'workspace';
    }

    public function label(): string
    {
        return 'Espaços de Trabalho';
    }

    public function minimumCharacters(): int
    {
        return 2;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array
    {
        $results = [];

        foreach ($this->workspaces->availableFor($user) as $workspace) {
            $text = ((string) ($workspace['title'] ?? '')).' '.((string) ($workspace['description'] ?? ''));

            if (! $this->containsTerm($text, $term)) {
                continue;
            }

            $results[] = [
                'type' => 'workspace',
                'group_key' => 'workspaces',
                'group_label' => $this->label(),
                'label' => (string) ($workspace['title'] ?? 'Workspace'),
                'subtitle' => (string) ($workspace['description'] ?? ''),
                'route_name' => 'workspaces.show',
                'route_parameters' => [(string) ($workspace['key'] ?? '')],
                'score' => 100,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
