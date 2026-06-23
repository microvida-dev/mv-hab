<?php

namespace App\Services\PublicPortal;

use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicContestService
{
    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return Builder<Contest>
     */
    public function query(array $filters = []): Builder
    {
        /** @var Builder<Contest> $query */
        $query = Contest::query();

        $this->publiclyVisibleContest($query)
            ->with(['program.municipality'])
            ->withCount(['contestHousingUnits as public_housing_units_count' => function (Builder $query): void {
                $query->whereHas('housingUnit', function (Builder $builder): void {
                    /** @var Builder<HousingUnit> $builder */
                    $this->publiclyVisibleHousing($builder);
                });
            }]);

        if (($term = $this->stringFilter($filters, 'q')) !== null) {
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('title', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if (($status = $this->stringFilter($filters, 'status')) !== null) {
            $this->applyPublicStatus($query, $status);
        }

        return $query;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return LengthAwarePaginator<int, Contest>
     */
    public function paginate(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderBy('closes_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findBySlug(string $slug): Contest
    {
        /** @var Builder<Contest> $query */
        $query = Contest::query();

        return $this->publiclyVisibleContest($query)
            ->where('slug', $slug)
            ->with([
                'program.municipality',
                'deadlines',
                'contestHousingUnits' => function ($query): void {
                    /** @var Builder<ContestHousingUnit> $query */
                    $query->whereHas('housingUnit', function (Builder $builder): void {
                        /** @var Builder<HousingUnit> $builder */
                        $this->publiclyVisibleHousing($builder);
                    });
                },
                'contestHousingUnits.housingUnit.coverImage',
                'contestHousingUnits.housingUnit.publicFeatures',
            ])
            ->firstOrFail();
    }

    /**
     * @param  Builder<Contest>  $query
     */
    private function applyPublicStatus(Builder $query, string $status): void
    {
        match ($status) {
            'open' => $query->where('opens_at', '<=', now())->where('closes_at', '>=', now()),
            'upcoming' => $query->where('opens_at', '>', now()),
            'closed' => $query->where('closes_at', '<', now()),
            default => $query,
        };
    }

    /**
     * @param  Builder<Contest>  $query
     * @return Builder<Contest>
     */
    private function publiclyVisibleContest(Builder $query): Builder
    {
        return (new Contest)->scopePubliclyVisible($query);
    }

    /**
     * @param  Builder<HousingUnit>  $query
     * @return Builder<HousingUnit>
     */
    private function publiclyVisibleHousing(Builder $query): Builder
    {
        return (new HousingUnit)->scopePubliclyVisible($query);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     */
    private function stringFilter(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
