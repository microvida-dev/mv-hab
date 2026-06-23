<?php

namespace App\Services\Contracts;

use App\Models\Contract;

class LeaseContractNumberService
{
    public function generate(): string
    {
        $year = now()->year;
        $sequence = Contract::query()->whereYear('created_at', $year)->count() + 1;

        do {
            $number = sprintf('CT-HAB-%s-%06d', $year, $sequence++);
        } while (Contract::query()->where('contract_number', $number)->exists());

        return $number;
    }
}
