<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\Allocation;
use App\Models\AllocationRun;
use App\Models\DefinitiveListEntry;
use Illuminate\Support\Collection;

final readonly class AllocationExecutionResult
{
    /**
     * @param  Collection<int, Allocation>  $allocations
     * @param  Collection<int, DefinitiveListEntry>  $reserveEntries
     */
    public function __construct(
        public AllocationRun $allocationRun,
        public Collection $allocations,
        public Collection $reserveEntries,
    ) {}
}
