<?php

namespace App\Services\Allocation;

use App\DataTransferObjects\LotteryExecutionResult;
use App\Enums\LotteryResultType;
use App\Enums\LotteryRunStatus;
use App\Enums\TypologyAdequacyResult;
use App\Exceptions\Allocation\LotteryAlreadyExecutedException;
use App\Models\Allocation;
use App\Models\AllocationRun;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\LotteryDrawResult;
use App\Models\LotteryParticipant;
use App\Models\LotteryRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class LotteryService
{
    public function lock(
        LotteryRun $lotteryRun,
        User $user,
    ): LotteryRun {
        return DB::transaction(function () use ($lotteryRun, $user): LotteryRun {
            if (! is_null($lotteryRun->locked_at)) {
                $freshLotteryRun = $lotteryRun->fresh();

                return $freshLotteryRun instanceof LotteryRun ? $freshLotteryRun : $lotteryRun;
            }

            $attributes = [
                'locked_at' => now(),
            ];

            if ($lotteryRun->isFillable('locked_by')) {
                $attributes['locked_by'] = $user->getKey();
            } elseif ($lotteryRun->isFillable('locked_by_user_id')) {
                $attributes['locked_by_user_id'] = $user->getKey();
            }

            if ($lotteryRun->isFillable('status')) {
                $attributes['status'] = 'locked';
            }

            $lotteryRun->fill($attributes);
            $lotteryRun->save();

            $freshLotteryRun = $lotteryRun->fresh();

            return $freshLotteryRun instanceof LotteryRun ? $freshLotteryRun : $lotteryRun;
        });
    }

    public function __construct(
        private readonly LotteryAuditService $auditService,
        private readonly RankingAllocationService $rankingAllocationService,
        private readonly TypologyAdequacyService $typologyAdequacyService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function run(
        AllocationRun $allocationRun,
        User $actor,
        array $options = []
    ): LotteryExecutionResult {
        return DB::transaction(
            fn () => $this->executeLottery(
                $allocationRun,
                $actor,
                $options
            )
        );
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function executeLottery(
        AllocationRun $allocationRun,
        User $actor,
        array $options
    ): LotteryExecutionResult {
        $allocationRun = $this->lockAllocationRun($allocationRun);

        $this->ensureLotteryNotExecuted($allocationRun);

        $seed = $this->auditService->seed(
            $options['seed'] ?? null
        );

        $lottery = $this->createLotteryRun(
            $allocationRun,
            $actor,
            $seed,
            $options
        );

        $this->registerParticipants(
            $lottery,
            $allocationRun
        );

        $lottery->load([
            'participants.definitiveListEntry.application',
        ]);

        $availableUnits = $this->loadAvailableUnits(
            $allocationRun
        );

        $orderedParticipants = $this->orderParticipants(
            $lottery,
            $seed
        );

        [$allocations, $reserveEntries] = $this->performDraw(
            $orderedParticipants,
            $availableUnits,
            $allocationRun,
            $lottery,
            $actor
        );

        $this->finalizeLottery(
            $lottery,
            $orderedParticipants->count(),
            $actor
        );

        return new LotteryExecutionResult(
            lotteryRun: $lottery->refresh()->load([
                'participants',
                'drawResults',
            ]),
            allocations: $allocations,
            reserveEntries: $reserveEntries,
        );
    }

    private function lockAllocationRun(
        AllocationRun $allocationRun
    ): AllocationRun {
        return AllocationRun::query()
            ->lockForUpdate()
            ->with('definitiveList')
            ->findOrFail($allocationRun->id);
    }

    private function ensureLotteryNotExecuted(
        AllocationRun $allocationRun
    ): void {
        $exists = LotteryRun::query()
            ->where('allocation_run_id', $allocationRun->id)
            ->whereIn('status', [
                LotteryRunStatus::Running,
                LotteryRunStatus::Locked,
            ])
            ->exists();

        if ($exists) {
            throw new LotteryAlreadyExecutedException;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function createLotteryRun(
        AllocationRun $allocationRun,
        User $actor,
        string $seed,
        array $options
    ): LotteryRun {
        $lottery = new LotteryRun([
            'allocation_run_id' => $allocationRun->id,
            'program_id' => $allocationRun->program_id,
            'contest_id' => $allocationRun->contest_id,
            'definitive_list_id' => $allocationRun->definitive_list_id,
            'lottery_method' => 'hash_seeded_order',
            'seed' => $seed,
            'seed_source' => $options['seed_source']
                ?? 'system_generated_or_operator_supplied',
            'algorithm' => $options['algorithm']
                ?? 'sha256(seed:participant)',
        ]);

        $lottery->forceFill([
            'status' => LotteryRunStatus::Running,
            'started_by' => $actor->id,
            'started_at' => now(),
        ])->save();

        return $lottery;
    }

    private function registerParticipants(
        LotteryRun $lottery,
        AllocationRun $allocationRun
    ): void {
        /** @var DefinitiveList $definitiveList */
        $definitiveList = $allocationRun->getRelationValue('definitiveList');
        $entries = DefinitiveListEntry::query()
            ->where('definitive_list_id', $definitiveList->id)
            ->eligibleForAllocation()
            ->orderBy('rank_position')
            ->get();

        foreach ($entries as $index => $entry) {
            $participant = new LotteryParticipant([
                'lottery_run_id' => $lottery->id,
                'application_id' => $entry->application_id,
                'user_id' => $entry->user_id,
                'definitive_list_entry_id' => $entry->id,
                'rank_position' => $entry->rank_position,
                'weight' => 1,
                'is_eligible' => true,
            ]);

            $participant->forceFill([
                'participant_number' => sprintf(
                    'PART-%06d',
                    $index + 1
                ),
            ]);

            $participant->save();
        }
    }

    /**
     * @return Collection<int, ContestHousingUnit>
     */
    private function loadAvailableUnits(
        AllocationRun $allocationRun
    ): Collection {
        return ContestHousingUnit::query()
            ->available()
            ->where('contest_id', $allocationRun->contest_id)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, LotteryParticipant>
     */
    private function orderParticipants(
        LotteryRun $lottery,
        string $seed
    ): Collection {
        /** @var Collection<int, LotteryParticipant> $participants */
        $participants = $lottery->participants;

        return $participants
            ->map(function (LotteryParticipant $participant) use ($seed) {
                $participant->setAttribute('random_value', $this->auditService->randomValue(
                    $seed,
                    $participant->participant_number
                ));

                return $participant;
            })
            ->sortBy('random_value')
            ->values();
    }

    /**
     * @param  Collection<int, LotteryParticipant>  $participants
     * @param  Collection<int, ContestHousingUnit>  $availableUnits
     * @return array{0: \Illuminate\Support\Collection<int, Allocation>, 1: \Illuminate\Support\Collection<int, DefinitiveListEntry>}
     */
    private function performDraw(
        Collection $participants,
        Collection $availableUnits,
        AllocationRun $allocationRun,
        LotteryRun $lottery,
        User $actor
    ): array {
        $allocations = collect();
        $reserveEntries = collect();

        foreach ($participants as $index => $participant) {

            /** @var DefinitiveListEntry $entry */
            $entry = $participant->getRelationValue('definitiveListEntry');
            /** @var Application $application */
            $application = $entry->getRelationValue('application');

            $unit = $availableUnits->first(
                fn (ContestHousingUnit $candidate) => $this->typologyAdequacyService->evaluate(
                    $application,
                    $candidate
                ) === TypologyAdequacyResult::Adequate
            );

            $selected = $unit !== null;

            $this->storeDrawResult(
                $lottery,
                $participant,
                $unit,
                $selected,
                $index + 1
            );

            if ($selected) {

                $allocations->push(
                    $this->rankingAllocationService
                        ->createAllocation(
                            $allocationRun,
                            $entry,
                            $unit,
                            $actor
                        )
                );

                $availableUnits = $availableUnits
                    ->reject(
                        fn (ContestHousingUnit $candidate) => $candidate->id === $unit->id
                    )
                    ->values();

                continue;
            }

            $reserveEntries->push($entry);
        }

        return [$allocations, $reserveEntries];
    }

    private function storeDrawResult(
        LotteryRun $lottery,
        LotteryParticipant $participant,
        ?ContestHousingUnit $unit,
        bool $selected,
        int $drawOrder
    ): void {
        $result = new LotteryDrawResult([
            'lottery_run_id' => $lottery->id,
            'lottery_participant_id' => $participant->id,
            'application_id' => $participant->application_id,
            'user_id' => $participant->user_id,
            'selected' => $selected,
            'assigned_contest_housing_unit_id' => $unit?->id,
            'assigned_housing_unit_id' => $unit?->housing_unit_id,
        ]);

        $result->forceFill([
            'draw_order' => $drawOrder,
            'result_type' => $selected
                ? LotteryResultType::Selected
                : LotteryResultType::Reserve,
            'random_value' => $participant->getAttribute('random_value'),
            'audit_data' => [
                'participant_number' => $participant->participant_number,
            ],
        ])->save();
    }

    private function finalizeLottery(
        LotteryRun $lottery,
        int $participantCount,
        User $actor
    ): void {
        $lottery = $lottery->refresh()->load([
            'participants',
            'drawResults',
        ]);

        $audit = $this->auditService->lockPayload($lottery);

        $lottery->forceFill([
            'status' => LotteryRunStatus::Locked,
            'participants_count' => $participantCount,
            'drawn_count' => $participantCount,
            'completed_at' => now(),
            'locked_at' => now(),
            'locked_by' => $actor->id,
            'audit_payload' => $audit['payload'],
            'audit_hash' => $audit['hash'],
        ])->save();

        $this->auditLogger->record(
            AuditEvents::CREATE,
            $lottery,
            'allocations',
            'lottery_run_execute',
            'Sorteio auditável executado.',
            metadata: [
                'audit_hash' => $lottery->audit_hash,
            ],
        );
    }
}
