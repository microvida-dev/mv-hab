<?php

namespace App\Services\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Models\HousingUnit;

class PublicHousingMapService
{
    public function __construct(private readonly PublicHousingSearchService $searchService) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return array<int, array<string, bool|float|int|string|null>>
     */
    public function markers(array $filters = []): array
    {
        return $this->searchService
            ->mapUnits($filters)
            ->map(fn (HousingUnit $unit) => [
                'id' => $unit->getKey(),
                'title' => $unit->displayTitle(),
                'url' => route('public.housing-units.show', $unit->public_slug),
                'typology' => $unit->typology,
                'monthly_rent' => $unit->monthly_rent,
                'public_status' => $this->publicStatusLabel($unit),
                'location' => $unit->publicLocationLabel(),
                'latitude' => (float) $unit->public_latitude,
                'longitude' => (float) $unit->public_longitude,
                'precision' => $this->precisionLabel($unit),
            ])
            ->values()
            ->all();
    }

    private function publicStatusLabel(HousingUnit $unit): ?string
    {
        $status = $unit->getAttribute('public_status');

        if ($status instanceof HousingPublicStatus) {
            return $status->label();
        }

        return is_string($status) && $status !== ''
            ? HousingPublicStatus::tryFrom($status)?->label()
            : null;
    }

    private function precisionLabel(HousingUnit $unit): ?string
    {
        $precision = $unit->getAttribute('public_location_precision');

        if ($precision instanceof HousingLocationPrecision) {
            return $precision->label();
        }

        return is_string($precision) && $precision !== ''
            ? HousingLocationPrecision::tryFrom($precision)?->label()
            : null;
    }
}
