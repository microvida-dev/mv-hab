<?php

namespace App\Services\Allocation;

use App\DataTransferObjects\AllocationExecutionResult;
use App\Enums\AllocationStatus;
use App\Enums\TypologyAdequacyResult;
use App\Models\AllocationRun;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveListEntry;
use App\Models\User;
use Illuminate\Support\Collection;

class PreferenceAllocationService extends RankingAllocationService
{
    public function allocate(AllocationRun $run, User $actor): AllocationExecutionResult
    {
        $entries = $this->requiredDefinitiveList($run)->entries()
            ->eligibleForAllocation()
            ->with('application.housingPreferences')
            ->orderBy('rank_position')
            ->get();
        $availableUnits = ContestHousingUnit::query()
            ->available()
            ->where('contest_id', $run->contest_id)
            ->orderBy('id')
            ->get();
        $allocations = collect();
        $reserveEntries = collect();

        foreach ($entries as $entry) {
            $unit = $this->preferredAdequateUnit($entry, $availableUnits) ?? $this->firstAdequateUnit($entry, $availableUnits);

            if (! $unit) {
                $reserveEntries->push($entry);

                continue;
            }

            $allocation = $this->createAllocation($run, $entry, $unit, $actor);
            $preference = $this->requiredApplication($entry)->housingPreferences->firstWhere('contest_housing_unit_id', $unit->id);
            if ($preference) {
                $allocation->forceFill(['preference_order' => $preference->preference_order, 'status' => AllocationStatus::Proposed])->save();
            }
            $allocations->push($allocation->refresh());
            $availableUnits = $availableUnits->reject(fn (ContestHousingUnit $candidate) => $candidate->id === $unit->id)->values();
        }

        return new AllocationExecutionResult(
            allocationRun: $run,
            allocations: $allocations,
            reserveEntries: $reserveEntries,
        );
    }

    /** @param  Collection<int, ContestHousingUnit>  $availableUnits */
    private function preferredAdequateUnit(DefinitiveListEntry $entry, Collection $availableUnits): ?ContestHousingUnit
    {
        $application = $this->requiredApplication($entry);

        foreach ($application->housingPreferences->sortBy('preference_order') as $preference) {
            $unit = $availableUnits->firstWhere('id', $preference->contest_housing_unit_id);
            if ($unit instanceof ContestHousingUnit && $this->typologyService->evaluate($application, $unit) === TypologyAdequacyResult::Adequate) {
                return $unit;
            }
        }

        return null;
    }
}
