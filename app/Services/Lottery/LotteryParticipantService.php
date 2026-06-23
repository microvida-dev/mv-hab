<?php

namespace App\Services\Lottery;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryParticipantStatus;
use App\Models\AllocationRun;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotteryParticipantService
{
    public function __construct(
        private readonly LotterySnapshotService $snapshots,
        private readonly AuditLogger $audit,
    ) {}

    public function loadFromDefinitiveList(LotteryDraw $draw, User $actor): LotteryDraw
    {
        return DB::transaction(function () use ($draw): LotteryDraw {
            $draw = LotteryDraw::query()
                ->with('allocationRun.definitiveList')
                ->lockForUpdate()
                ->findOrFail($draw->id);

            if ($draw->participants_locked_at !== null) {
                throw ValidationException::withMessages(['lottery_draw' => 'Os participantes já estão bloqueados.']);
            }

            $definitiveListId = $draw->definitive_list_id;

            if ($draw->allocation_run_id !== null) {
                $allocationDefinitiveListId = AllocationRun::query()
                    ->whereKey($draw->allocation_run_id)
                    ->value('definitive_list_id');

                $definitiveListId = $allocationDefinitiveListId === null ? $definitiveListId : (int) $allocationDefinitiveListId;
            }

            $definitiveList = $definitiveListId === null ? null : DefinitiveList::find($definitiveListId);

            if ($definitiveList === null) {
                throw ValidationException::withMessages(['definitive_list_id' => 'O sorteio não tem lista definitiva associada.']);
            }

            $entries = DefinitiveListEntry::query()
                ->where('definitive_list_id', $definitiveList->id)
                ->eligibleForAllocation()
                ->with(['application', 'candidate'])
                ->orderBy('rank_position')
                ->get();

            if ($entries->isEmpty()) {
                throw ValidationException::withMessages(['participants' => 'Não existem candidatos elegíveis para carregar.']);
            }

            foreach ($entries as $index => $entry) {
                /** @var DefinitiveListEntry $entry */
                $participant = LotteryParticipant::query()->firstOrNew([
                    'lottery_run_id' => $draw->id,
                    'application_id' => $entry->application_id,
                ]);

                $participant->fill([
                    'user_id' => $entry->user_id,
                    'definitive_list_entry_id' => $entry->id,
                    'rank_position' => $entry->rank_position,
                    'previous_score' => $entry->total_score,
                    'status' => LotteryParticipantStatus::Included,
                    'weight' => 1,
                    'is_eligible' => true,
                    'snapshot' => [
                        'public_identifier' => $entry->public_identifier,
                        'entry_status' => (string) $entry->getRawOriginal('status'),
                        'rank_position' => $entry->rank_position,
                        'total_score' => $entry->total_score,
                    ],
                    'included_at' => now(),
                ]);

                $participant->forceFill([
                    'participant_number' => sprintf('DRAW-%06d', $index + 1),
                ])->save();
            }

            $draw->forceFill([
                'status' => LotteryDrawStatus::ParticipantsLoaded,
                'participants_count' => $entries->count(),
            ])->save();

            $this->audit->record(AuditEvents::UPDATE, $draw, 'allocations', 'lottery_participants_load', 'Participantes do sorteio carregados.', metadata: [
                'participants_count' => $entries->count(),
            ]);

            return $draw->refresh()->load('participants');
        });
    }

    public function lock(LotteryDraw $draw, User $actor): LotteryDraw
    {
        return DB::transaction(function () use ($draw, $actor): LotteryDraw {
            $draw = LotteryDraw::query()
                ->with('participants')
                ->lockForUpdate()
                ->findOrFail($draw->id);

            if ($draw->participants->isEmpty()) {
                throw ValidationException::withMessages(['participants' => 'Carregue participantes antes de bloquear o sorteio.']);
            }

            $hash = $this->snapshots->hashParticipants($draw->participants);

            $draw->forceFill([
                'status' => LotteryDrawStatus::ParticipantsLocked,
                'participants_locked_at' => now(),
                'participants_locked_by' => $actor->id,
                'participants_hash' => $hash,
                'participants_count' => $draw->participants->count(),
            ])->save();

            $this->audit->record(AuditEvents::UPDATE, $draw, 'allocations', 'lottery_participants_lock', 'Participantes do sorteio bloqueados.', metadata: [
                'participants_hash' => $hash,
            ]);

            return $draw->refresh();
        });
    }
}
