<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\Allocation;
use App\Models\DefinitiveListEntry;
use App\Models\LotteryRun;
use Illuminate\Support\Collection;

/**
 * @phpstan-type AllocationCollection Collection<int, Allocation>
 * @phpstan-type ReserveEntryCollection Collection<int, DefinitiveListEntry>
 */
final readonly class LotteryExecutionResult
{
    /**
     * @param  Collection<int, Allocation>  $allocations
     * @param  Collection<int, DefinitiveListEntry>  $reserveEntries
     */
    public function __construct(
        public LotteryRun $lotteryRun,
        public Collection $allocations,
        public Collection $reserveEntries,
    ) {}
}
