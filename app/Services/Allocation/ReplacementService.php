<?php

namespace App\Services\Allocation;

use App\Enums\ReserveListEntryStatus;
use App\Models\Allocation;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveListEntry;
use App\Models\ReserveListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class ReplacementService
{
    public function __construct(
        private readonly ReserveListService $reserveListService,
        private readonly RankingAllocationService $rankingAllocationService,
        private readonly AllocationOfferService $offerService,
        private readonly ContestHousingUnitService $contestHousingUnitService,
        private readonly AllocationNotificationService $notificationService,
        private readonly TypologyAdequacyService $typologyService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function callNextFor(Allocation $previousAllocation, User $actor): ?Allocation
    {
        $allocationRun = $this->requiredAllocationRun($previousAllocation);
        $reserveList = $allocationRun->reserveList;
        if (! $reserveList) {
            return null;
        }

        $unit = ContestHousingUnit::query()->find($previousAllocation->contest_housing_unit_id);
        if (! $unit) {
            return null;
        }

        $this->contestHousingUnitService->release($unit, $actor);

        while ($entry = $this->reserveListService->nextWaiting($reserveList)) {
            $this->reserveListService->markCalled($entry, $actor);

            if ($this->typologyService->evaluate($this->requiredApplication($entry), $unit->refresh())->value !== 'adequate') {
                $entry->forceFill(['status' => ReserveListEntryStatus::Removed, 'removed_at' => now()])->save();

                continue;
            }

            $allocation = $this->rankingAllocationService->createAllocation(
                $allocationRun,
                $this->requiredDefinitiveListEntry($entry),
                $unit->refresh(),
                $actor,
                $entry->reserve_position,
            );

            $previousAllocation->forceFill(['superseded_by_allocation_id' => $allocation->id])->save();
            $offer = $this->offerService->createAndIssue($allocation, $actor);
            $this->reserveListService->markOffered($entry->refresh(), $actor, $allocation);
            $entry->forceFill(['replacement_for_allocation_id' => $previousAllocation->id])->save();
            $this->notificationService->reserveCalled($allocation, $actor);
            $this->auditLogger->record(AuditEvents::UPDATE, $allocation, 'allocations', 'replacement_allocate', 'Suplente chamado para substituição.', metadata: ['previous_allocation_id' => $previousAllocation->id, 'offer_id' => $offer->id]);

            return $allocation->refresh();
        }

        return null;
    }

    private function requiredAllocationRun(Allocation $allocation): AllocationRun
    {
        $run = $allocation->allocationRun;

        if (! $run instanceof AllocationRun) {
            throw ValidationException::withMessages(['allocation_run' => 'A atribuição não tem execução associada.']);
        }

        return $run;
    }

    private function requiredApplication(ReserveListEntry $entry): Application
    {
        $application = $entry->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'A entrada de suplente não tem candidatura associada.']);
        }

        return $application;
    }

    private function requiredDefinitiveListEntry(ReserveListEntry $entry): DefinitiveListEntry
    {
        $definitiveListEntry = $entry->definitiveListEntry;

        if (! $definitiveListEntry instanceof DefinitiveListEntry) {
            throw ValidationException::withMessages(['definitive_list_entry' => 'A entrada de suplente não tem entrada definitiva associada.']);
        }

        return $definitiveListEntry;
    }
}
