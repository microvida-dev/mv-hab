<?php

namespace App\Services\Search\Sources;

use App\Models\Citizen;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;

class CandidateSearchSource implements SearchSource
{
    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'candidate';
    }

    public function label(): string
    {
        return 'Munícipes/Candidatos';
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
        if (! $this->authorization->canAccess($user, 'citizens.show', 'citizens.view')) {
            return [];
        }

        return array_values(Citizen::query()
            ->select(['id', 'name', 'created_at'])
            ->where('name', 'like', '%'.$term.'%')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Citizen $citizen): array => [
                'type' => 'candidate',
                'group_key' => 'candidates',
                'group_label' => $this->label(),
                'label' => $citizen->name,
                'subtitle' => 'Ficha de munícipe autorizada',
                'route_name' => 'citizens.show',
                'route_parameters' => [$citizen->getKey()],
                'score' => 85,
            ])
            ->all());
    }
}
