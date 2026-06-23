<?php

namespace App\Services\Reporting\Indicators;

use App\Enums\AllocationStatus;
use App\Models\Allocation;
use App\Models\ReserveListEntry;
use App\Services\Reporting\ReportFilterService;
use Illuminate\Database\Eloquent\Builder;

class AllocationIndicatorsService
{
    public function __construct(private readonly ReportFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Allocation>
     */
    private function query(array $filters): Builder
    {
        return $this->filters->applyApplication(Allocation::query(), $filters, 'allocated_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countAllocations(array $filters): int
    {
        return $this->query($filters)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countAcceptedAllocations(array $filters): int
    {
        return $this->query($filters)->where('status', AllocationStatus::Accepted->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countRefusedAllocations(array $filters): int
    {
        return $this->query($filters)->where('status', AllocationStatus::Refused->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countPendingAllocationResponses(array $filters): int
    {
        return $this->query($filters)->where('status', AllocationStatus::Offered->value)->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function countActiveReserveEntries(array $filters): int
    {
        return ReserveListEntry::query()->where('status', 'active')->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function allocationRate(array $filters): float
    {
        $total = $this->countAllocations($filters);

        return $total === 0 ? 0 : round($this->countAcceptedAllocations($filters) * 100 / $total, 2);
    }
}
