<?php

namespace App\Services\Contracts;

class RentEffortRateService
{
    public function calculate(float $rent, float $monthlyIncome): ?float
    {
        if ($monthlyIncome <= 0) {
            return null;
        }

        return round(($rent / $monthlyIncome) * 100, 4);
    }
}
