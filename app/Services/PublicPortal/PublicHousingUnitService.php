<?php

namespace App\Services\PublicPortal;

use App\Models\HousingUnit;

class PublicHousingUnitService
{
    public function findBySlug(string $slug): HousingUnit
    {
        return HousingUnit::query()
            ->publiclyVisible()
            ->where('public_slug', $slug)
            ->with([
                'municipality',
                'publicFeatures',
                'publicImages',
                'coverImage',
                'publicDocuments.contest',
                'contestHousingUnits.contest.program.municipality',
            ])
            ->firstOrFail();
    }
}
