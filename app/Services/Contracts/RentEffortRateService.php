<?php

namespace App\Services\Contracts;

use App\Support\DecimalMoney;

class RentEffortRateService
{
    public function calculate(int|string $rent, int|string $monthlyIncome): ?string
    {
        return DecimalMoney::ratioPercentage($rent, $monthlyIncome);
    }
}
