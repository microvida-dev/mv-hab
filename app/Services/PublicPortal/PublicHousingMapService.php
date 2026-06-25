<?php

namespace App\Services\PublicPortal;

use App\Models\HousingUnit;

class PublicHousingMapService
{
    public function __construct(
        private readonly PublicHousingSearchService $searchService,
        private readonly PublicMapPayloadService $payloadService,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function markers(array $filters = []): array
    {
        return $this->searchService
            ->mapUnits($filters)
            ->map(fn (HousingUnit $unit): array => $this->payloadService->marker($unit))
            ->values()
            ->all();
    }
}
