<?php

namespace App\Services\PublicPortal;

use App\Enums\HousingPublicStatus;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\Program;
use App\Models\VisitSlot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PublicHousingSearchService
{
    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return Builder<HousingUnit>
     */
    public function query(array $filters = []): Builder
    {
        /** @var Builder<HousingUnit> $query */
        $query = HousingUnit::query();

        $this->publiclyVisibleHousing($query)->with([
            'municipality',
            'coverImage',
            'publicImages',
            'publicFeatures',
            'publicDocuments',
            'contestHousingUnits.contest.program.municipality',
        ]);

        if (($term = $this->stringFilter($filters, 'q')) !== null) {
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('public_title', 'like', "%{$term}%")
                    ->orWhere('public_summary', 'like', "%{$term}%")
                    ->orWhere('public_description', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('parish', 'like', "%{$term}%")
                    ->orWhere('locality', 'like', "%{$term}%");
            });
        }

        if (($typology = $this->stringFilter($filters, 'typology')) !== null) {
            $query->where('typology', $typology);
        }

        if (($parish = $this->stringFilter($filters, 'parish')) !== null) {
            $query->where('parish', $parish);
        }

        if (($locality = $this->stringFilter($filters, 'locality')) !== null) {
            $query->where('locality', $locality);
        }

        if (($zone = $this->stringFilter($filters, 'zone')) !== null) {
            $query->where('public_location_description', 'like', "%{$zone}%");
        }

        if (($status = $this->stringFilter($filters, 'public_status')) !== null) {
            $query->where('public_status', $status);
        }

        if (is_numeric($filters['rent_min'] ?? null)) {
            $query->where('monthly_rent', '>=', (float) $filters['rent_min']);
        }

        if (is_numeric($filters['rent_max'] ?? null)) {
            $query->where('monthly_rent', '<=', (float) $filters['rent_max']);
        }

        if ($this->booleanFilter($filters, 'accessible')) {
            $query->whereHas('contestHousingUnits', fn (Builder $builder) => $builder->where('accessible', true));
        }

        if (($energyRating = $this->stringFilter($filters, 'energy_rating')) !== null) {
            $query->where('energy_rating', $energyRating);
        }

        if ($this->booleanFilter($filters, 'visit_available')) {
            $query->whereHas('visitSlots', function (Builder $builder): void {
                /** @var Builder<VisitSlot> $builder */
                (new VisitSlot)->scopeAvailable($builder);
            });
        }

        if (($programSlug = $this->stringFilter($filters, 'program')) !== null) {
            $query->whereHas('contestHousingUnits.contest.program', function (Builder $builder) use ($programSlug): void {
                /** @var Builder<Program> $builder */
                (new Program)->scopePubliclyVisible($builder)->where('slug', $programSlug);
            });
        }

        if (($contestSlug = $this->stringFilter($filters, 'contest')) !== null) {
            $query->whereHas('contestHousingUnits.contest', function (Builder $builder) use ($contestSlug): void {
                /** @var Builder<Contest> $builder */
                $this->publiclyVisibleContest($builder)->where('slug', $contestSlug);
            });
        }

        if (($contestStatus = $this->stringFilter($filters, 'contest_status')) !== null) {
            $this->applyContestStatus($query, $contestStatus);
        }

        return $query;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return LengthAwarePaginator<int, HousingUnit>
     */
    public function paginate(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->sort($this->query($filters), (string) ($filters['sort'] ?? 'published_desc'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        /** @var Builder<HousingUnit> $base */
        $base = HousingUnit::query();
        $base = $this->publiclyVisibleHousing($base);

        return [
            'typologies' => (clone $base)->select('typology')->whereNotNull('typology')->distinct()->orderBy('typology')->pluck('typology'),
            'parishes' => (clone $base)->select('parish')->whereNotNull('parish')->distinct()->orderBy('parish')->pluck('parish'),
            'localities' => (clone $base)->select('locality')->whereNotNull('locality')->distinct()->orderBy('locality')->pluck('locality'),
            'statuses' => collect(HousingPublicStatus::cases())->mapWithKeys(fn (HousingPublicStatus $status) => [$status->value => $status->label()]),
            'energy_ratings' => (clone $base)->select('energy_rating')->whereNotNull('energy_rating')->distinct()->orderBy('energy_rating')->pluck('energy_rating'),
            'rent_min' => (clone $base)->min('monthly_rent'),
            'rent_max' => (clone $base)->max('monthly_rent'),
        ];
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return Collection<int, HousingUnit>
     */
    public function mapUnits(array $filters = [], int $limit = 200): Collection
    {
        return $this->publicOrder($this->query($filters)
            ->whereNotNull('public_latitude')
            ->whereNotNull('public_longitude'))
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Builder<HousingUnit>  $query
     * @return Builder<HousingUnit>
     */
    private function sort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'rent_asc' => $this->publicOrder($query->orderBy('monthly_rent')),
            'rent_desc' => $this->publicOrder($query->orderByDesc('monthly_rent')),
            'typology' => $this->publicOrder($query->orderBy('typology')),
            default => $this->publicOrder($query),
        };
    }

    /**
     * @param  Builder<HousingUnit>  $query
     */
    private function applyContestStatus(Builder $query, string $status): void
    {
        $query->whereHas('contestHousingUnits.contest', function (Builder $builder) use ($status): void {
            /** @var Builder<Contest> $builder */
            $this->publiclyVisibleContest($builder);

            match ($status) {
                'open' => $builder->where('opens_at', '<=', now())->where('closes_at', '>=', now()),
                'upcoming' => $builder->where('opens_at', '>', now()),
                'closed' => $builder->where('closes_at', '<', now()),
                default => $builder,
            };
        });
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
     * @param  Builder<HousingUnit>  $query
     * @return Builder<HousingUnit>
     */
    private function publicOrder(Builder $query): Builder
    {
        return (new HousingUnit)->scopePublicOrder($query);
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
     * @param  array<string, bool|float|int|string|null>  $filters
     */
    private function stringFilter(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     */
    private function booleanFilter(array $filters, string $key): bool
    {
        return filter_var($filters[$key] ?? false, FILTER_VALIDATE_BOOL);
    }
}
