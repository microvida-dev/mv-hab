<?php

namespace App\Services\Search\Sources;

use App\Models\HousingUnit;
use App\Models\User;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Search\Sources\Concerns\BuildsSearchResults;
use Illuminate\Database\Eloquent\Builder;

class HousingUnitSearchSource implements SearchSource
{
    use BuildsSearchResults;

    public function __construct(private readonly SearchResultAuthorizationService $authorization) {}

    public function key(): string
    {
        return 'housing_unit';
    }

    public function label(): string
    {
        return 'Património';
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
        if (! $this->authorization->canAccess($user, 'housing-units.show', 'housing_units.view')) {
            return [];
        }

        return array_values(HousingUnit::query()
            ->select(['id', 'code', 'typology', 'status', 'public_reference', 'public_title', 'parish', 'locality', 'created_at'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('code', 'like', '%'.$term.'%')
                    ->orWhere('public_reference', 'like', '%'.$term.'%')
                    ->orWhere('public_title', 'like', '%'.$term.'%')
                    ->orWhere('typology', 'like', '%'.$term.'%')
                    ->orWhere('parish', 'like', '%'.$term.'%')
                    ->orWhere('locality', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (HousingUnit $housingUnit): array => [
                'type' => 'housing_unit',
                'group_key' => 'housing',
                'group_label' => $this->label(),
                'label' => $housingUnit->displayTitle(),
                'subtitle' => collect([$housingUnit->typology, $housingUnit->parish, $housingUnit->locality])
                    ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                    ->join(' · '),
                'route_name' => 'housing-units.show',
                'route_parameters' => [$housingUnit->getKey()],
                'score' => 78,
            ])
            ->all());
    }
}
