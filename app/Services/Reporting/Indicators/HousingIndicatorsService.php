<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\HousingUnitStatus;
use App\Models\HousingUnit;
use Illuminate\Database\Eloquent\Builder;

class HousingIndicatorsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<HousingUnit>
     */
    private function query(array $filters): Builder
    {
        return HousingUnit::query()
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('address', 'like', '%'.$location.'%'))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['contest_id'] ?? null, fn (Builder $query, int $id) => $query->whereHas('contestHousingUnits', fn (Builder $link) => $link->where('contest_id', $id)));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countAvailableHousingUnits(array $filters): int
    {
        return $this->query($filters)->where('status', HousingUnitStatus::Available->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countAllocatedHousingUnits(array $filters): int
    {
        return $this->query($filters)->whereHas('allocations')->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countContractedHousingUnits(array $filters): int
    {
        return $this->query($filters)->whereHas('leaseContracts')->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countOccupiedHousingUnits(array $filters): int
    {
        return $this->query($filters)->where('status', HousingUnitStatus::Occupied->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countHousingUnitsUnderMaintenance(array $filters): int
    {
        return $this->query($filters)->where('status', HousingUnitStatus::Maintenance->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function occupancyRate(array $filters): float
    {
        $total = $this->query($filters)->where('status', '!=', HousingUnitStatus::Inactive->value)->count();

        return $total === 0 ? 0 : round($this->countOccupiedHousingUnits($filters) * 100 / $total, 2);
    }
}
