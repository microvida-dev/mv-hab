<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Route;
use Throwable;

class SearchResultPresenter
{
    /**
     * @param  list<array<string, mixed>>  $results
     * @return list<array{key: string, label: string, results: list<array<string, mixed>>}>
     */
    public function grouped(array $results, int $globalLimit = 40): array
    {
        $groups = [];
        $total = 0;

        foreach ($results as $result) {
            if ($total >= $globalLimit) {
                break;
            }

            $presented = $this->present($result);

            if ($presented === null) {
                continue;
            }

            $groupKey = (string) ($presented['group_key'] ?? 'outros');
            $groups[$groupKey] ??= [
                'key' => $groupKey,
                'label' => (string) ($presented['group_label'] ?? 'Resultados'),
                'results' => [],
            ];

            $groups[$groupKey]['results'][] = $presented;
            $total++;
        }

        return array_values($groups);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>|null
     */
    public function present(array $result): ?array
    {
        $routeName = $result['route_name'] ?? null;

        if (! is_string($routeName) || ! Route::has($routeName)) {
            return null;
        }

        $parameters = $result['route_parameters'] ?? [];

        if (! is_array($parameters)) {
            $parameters = [];
        }

        try {
            $url = route($routeName, $parameters);
        } catch (Throwable) {
            return null;
        }

        return [
            'type' => (string) ($result['type'] ?? 'result'),
            'group_key' => (string) ($result['group_key'] ?? 'outros'),
            'group_label' => (string) ($result['group_label'] ?? 'Resultados'),
            'label' => (string) ($result['label'] ?? 'Resultado'),
            'subtitle' => (string) ($result['subtitle'] ?? ''),
            'url' => $url,
            'route_name' => $routeName,
            'score' => (int) ($result['score'] ?? 0),
        ];
    }
}
