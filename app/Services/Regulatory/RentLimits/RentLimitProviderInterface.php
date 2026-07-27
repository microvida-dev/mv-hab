<?php

namespace App\Services\Regulatory\RentLimits;

use App\Data\Regulatory\RentLimitResult;
use App\Models\AffordableRentRegulatoryProfile;
use App\Models\RentRuleSet;
use Carbon\CarbonInterface;

interface RentLimitProviderInterface
{
    public function supports(AffordableRentRegulatoryProfile $profile): bool;

    public function limitsFor(
        AffordableRentRegulatoryProfile $profile,
        ?RentRuleSet $ruleSet,
        CarbonInterface $referenceDate,
    ): RentLimitResult;
}
