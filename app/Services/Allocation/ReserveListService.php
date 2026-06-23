<?php

namespace App\Services\Allocation;

use App\Enums\ReserveListEntryStatus;
use App\Enums\ReserveListStatus;
use App\Models\Allocation;
use App\Models\AllocationRun;
use App\Models\DefinitiveListEntry;
use App\Models\ReserveList;
use App\Models\ReserveListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReserveListService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  Collection<int, DefinitiveListEntry>  $entries
     */
    public function createForRun(AllocationRun $run, Collection $entries, User $actor): ReserveList
    {
        $reserve = new ReserveList([
            'allocation_run_id' => $run->id,
            'program_id' => $run->program_id,
            'contest_id' => $run->contest_id,
            'definitive_list_id' => $run->definitive_list_id,
        ]);
        $reserve->forceFill([
            'status' => ReserveListStatus::Active,
            'generated_by' => $actor->id,
            'generated_at' => now(),
        ])->save();

        $position = 1;
        foreach ($entries as $entry) {
            $this->createEntry($reserve, $run, $entry, $position++);
        }

        $this->auditLogger->record(AuditEvents::CREATE, $reserve, 'allocations', 'reserve_list_create', 'Lista de reserva criada.');

        $reserve->refresh();
        $reserve->load('entries');

        return $reserve;
    }

    public function nextWaiting(ReserveList $reserveList): ?ReserveListEntry
    {
        return $reserveList->entries()->waiting()->orderBy('reserve_position')->first();
    }

    public function markCalled(ReserveListEntry $entry, User $actor): ReserveListEntry
    {
        if ($entry->status !== ReserveListEntryStatus::Waiting) {
            throw ValidationException::withMessages(['reserve_list_entry' => 'O suplente não está em espera.']);
        }

        $entry->forceFill(['status' => ReserveListEntryStatus::Called, 'called_at' => now()])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $entry, 'allocations', 'reserve_candidate_call', 'Candidato suplente chamado.');

        return $entry->refresh();
    }

    public function markOffered(ReserveListEntry $entry, User $actor, Allocation $allocation): ReserveListEntry
    {
        $entry->forceFill(['status' => ReserveListEntryStatus::Offered, 'offered_at' => now(), 'linked_allocation_id' => $allocation->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $entry, 'allocations', 'reserve_candidate_offer', 'Oferta criada para suplente.');

        return $entry->refresh();
    }

    public function lock(ReserveList $reserveList, User $actor): ReserveList
    {
        $reserveList->forceFill(['status' => ReserveListStatus::Locked, 'locked_at' => now(), 'locked_by' => $actor->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $reserveList, 'allocations', 'reserve_list_lock', 'Lista de reserva bloqueada.');

        return $reserveList->refresh();
    }

    private function createEntry(ReserveList $reserve, AllocationRun $run, DefinitiveListEntry $entry, int $position): ReserveListEntry
    {
        $entryModel = new ReserveListEntry([
            'reserve_list_id' => $reserve->id,
            'allocation_run_id' => $run->id,
            'application_id' => $entry->application_id,
            'user_id' => $entry->user_id,
            'definitive_list_entry_id' => $entry->id,
            'reserve_position' => $position,
        ]);
        $entryModel->forceFill([
            'status' => ReserveListEntryStatus::Waiting,
        ])->save();

        return $entryModel->refresh();
    }
}
