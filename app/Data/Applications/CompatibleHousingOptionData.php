<?php

namespace App\Data\Applications;

use App\Models\ContestHousingUnit;

final readonly class CompatibleHousingOptionData
{
    public function __construct(
        public ContestHousingUnit $unit,
        public HousingCompatibilityResult $compatibility,
    ) {}
}
