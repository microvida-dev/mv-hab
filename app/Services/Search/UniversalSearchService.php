<?php

namespace App\Services\Search;

use App\Models\User;
use Throwable;

class UniversalSearchService
{
    public function __construct(
        private readonly SearchSourceRegistry $registry,
        private readonly SearchResultPresenter $presenter,
    ) {}

    /**
     * @return array{term: string, groups: list<array{key: string, label: string, results: list<array<string, mixed>>}>, minimum_characters: int}
     */
    public function search(User $user, string $term, int $limitPerSource = 5, int $globalLimit = 40): array
    {
        $normalizedTerm = trim($term);
        $results = [];

        foreach ($this->registry->sources() as $source) {
            if (mb_strlen($normalizedTerm) < $source->minimumCharacters()) {
                continue;
            }

            try {
                $results = [
                    ...$results,
                    ...$source->search($user, $normalizedTerm, $limitPerSource),
                ];
            } catch (Throwable) {
                continue;
            }
        }

        usort($results, fn (array $first, array $second): int => ((int) ($second['score'] ?? 0)) <=> ((int) ($first['score'] ?? 0)));

        return [
            'term' => $normalizedTerm,
            'groups' => $this->presenter->grouped($results, $globalLimit),
            'minimum_characters' => 2,
        ];
    }

    /**
     * @return array{term: string, groups: list<array{key: string, label: string, results: list<array<string, mixed>>}>}
     */
    public function commands(User $user, string $term = '', int $limit = 20): array
    {
        $normalizedTerm = trim($term);

        return [
            'term' => $normalizedTerm,
            'groups' => $this->presenter->grouped(
                $this->registry->commands()->search($user, $normalizedTerm, $limit),
                $limit,
            ),
        ];
    }
}
