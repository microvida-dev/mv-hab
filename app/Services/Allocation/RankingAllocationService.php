<?php

namespace App\Services\Allocation;

use App\DataTransferObjects\AllocationExecutionResult;
use App\Enums\AllocationStatus;
use App\Enums\ContestHousingUnitStatus;
use App\Enums\TypologyAdequacyResult;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RankingAllocationService
{
    public function __construct(
        protected readonly TypologyAdequacyService $typologyService,
        protected readonly ContestHousingUnitService $contestHousingUnitService,
    ) {}

    public function allocate(AllocationRun $run, User $actor): AllocationExecutionResult
    {
        $entries = $this->requiredDefinitiveList($run)->entries()
            ->eligibleForAllocation()
            ->orderBy('rank_position')
            ->get();
        $availableUnits = ContestHousingUnit::query()
            ->available()
            ->where('contest_id', $run->contest_id)
            ->orderBy('id')
            ->get();

        if ($availableUnits->isEmpty()) {
            throw ValidationException::withMessages(['contest_housing_units' => 'Não existem habitações disponíveis para atribuição.']);
        }

        $allocations = collect();
        $reserveEntries = collect();

        foreach ($entries as $entry) {
            $unit = $this->firstAdequateUnit($entry, $availableUnits);

            if (! $unit) {
                $reserveEntries->push($entry);

                continue;
            }

            $allocations->push($this->createAllocation($run, $entry, $unit, $actor));
            $availableUnits = $availableUnits->reject(fn (ContestHousingUnit $candidate) => $candidate->id === $unit->id)->values();
        }

        return new AllocationExecutionResult(
            allocationRun: $run,
            allocations: $allocations,
            reserveEntries: $reserveEntries,
        );
    }

    public function createAllocation(AllocationRun $run, DefinitiveListEntry $entry, ContestHousingUnit $unit, User $actor, ?int $reservePosition = null): Allocation
    {
        $this->assertNoActiveDuplicate($entry, $unit);
        $this->contestHousingUnitService->assertAvailable($unit);

        $allocation = new Allocation([
            'allocation_run_id' => $run->id,
            'allocation_rule_set_id' => $run->allocation_rule_set_id,
            'program_id' => $run->program_id,
            'contest_id' => $run->contest_id,
            'definitive_list_id' => $run->definitive_list_id,
            'definitive_list_entry_id' => $entry->id,
            'application_id' => $entry->application_id,
            'user_id' => $entry->user_id,
            'contest_housing_unit_id' => $unit->id,
            'housing_unit_id' => $unit->housing_unit_id,
            'allocation_method' => $run->allocation_method,
            'rank_position' => $entry->rank_position,
            'reserve_position' => $reservePosition,
            'preference_order' => null,
        ]);
        $allocation->forceFill([
            'status' => AllocationStatus::Proposed,
            'allocated_by' => $actor->id,
            'allocated_at' => now(),
            'acceptance_deadline_at' => now()->addDays($this->requiredAllocationRuleSet($run)->acceptance_deadline_days),
        ])->save();

        $this->contestHousingUnitService->markReserved($unit->refresh(), $actor);

        return $allocation->refresh();
    }

    /** @param  Collection<int, ContestHousingUnit>  $availableUnits */
    protected function firstAdequateUnit(DefinitiveListEntry $entry, Collection $availableUnits): ?ContestHousingUnit
    {
        $application = $this->requiredApplication($entry);

        foreach ($availableUnits as $unit) {
            if ($this->typologyService->evaluate($application, $unit) === TypologyAdequacyResult::Adequate) {
                return $unit;
            }
        }

        return null;
    }

    private function assertNoActiveDuplicate(DefinitiveListEntry $entry, ContestHousingUnit $unit): void
    {
        $candidateHasAllocation = Allocation::query()
            ->active()
            ->where('contest_id', $this->requiredDefinitiveListForEntry($entry)->contest_id)
            ->where('application_id', $entry->application_id)
            ->exists();

        if ($candidateHasAllocation) {
            throw ValidationException::withMessages(['application_id' => 'A candidatura já tem uma atribuição ativa neste concurso.']);
        }

        $unitHasAllocation = Allocation::query()
            ->active()
            ->where('contest_housing_unit_id', $unit->id)
            ->exists();

        if ($unitHasAllocation || $this->contestHousingUnitStatus($unit) !== ContestHousingUnitStatus::Available) {
            throw ValidationException::withMessages(['contest_housing_unit_id' => 'A habitação já tem uma atribuição ativa.']);
        }
    }

    protected function requiredDefinitiveList(AllocationRun $run): DefinitiveList
    {
        $list = $run->definitiveList;

        if (! $list instanceof DefinitiveList) {
            throw ValidationException::withMessages(['definitive_list' => 'A execução de atribuição não tem lista definitiva associada.']);
        }

        return $list;
    }

    protected function requiredApplication(DefinitiveListEntry $entry): Application
    {
        $application = $entry->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'A entrada da lista não tem candidatura associada.']);
        }

        return $application;
    }

    private function requiredAllocationRuleSet(AllocationRun $run): AllocationRuleSet
    {
        $ruleSet = $run->allocationRuleSet;

        if (! $ruleSet instanceof AllocationRuleSet) {
            throw ValidationException::withMessages(['allocation_rule_set' => 'A execução de atribuição não tem regras associadas.']);
        }

        return $ruleSet;
    }

    private function requiredDefinitiveListForEntry(DefinitiveListEntry $entry): DefinitiveList
    {
        $list = $entry->definitiveList;

        if (! $list instanceof DefinitiveList) {
            throw ValidationException::withMessages(['definitive_list' => 'A entrada não tem lista definitiva associada.']);
        }

        return $list;
    }

    private function contestHousingUnitStatus(ContestHousingUnit $unit): ?ContestHousingUnitStatus
    {
        $status = $unit->getAttribute('status');

        if ($status instanceof ContestHousingUnitStatus) {
            return $status;
        }

        return is_string($status) ? ContestHousingUnitStatus::tryFrom($status) : null;
    }
}
