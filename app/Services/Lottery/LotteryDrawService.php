<?php

namespace App\Services\Lottery;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryDrawType;
use App\Enums\LotteryResultStatus;
use App\Enums\LotteryResultType;
use App\Models\AllocationRun;
use App\Models\LotteryDraw;
use App\Models\LotteryResult;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotteryDrawService
{
    public function __construct(
        private readonly AuditableLotteryEngine $engine,
        private readonly LotterySnapshotService $snapshots,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): LotteryDraw
    {
        /** @var AllocationRun $allocationRun */
        $allocationRun = AllocationRun::query()->with('definitiveList')->findOrFail((int) $data['allocation_run_id']);

        $draw = new LotteryDraw([
            'allocation_run_id' => $allocationRun->id,
            'program_id' => $allocationRun->program_id,
            'contest_id' => $allocationRun->contest_id,
            'definitive_list_id' => $allocationRun->definitive_list_id,
            'draw_type' => $data['draw_type'] ?? LotteryDrawType::General->value,
            'lottery_method' => 'hash_seeded_order',
            'seed' => $data['seed'] ?? bin2hex(random_bytes(16)),
            'seed_source' => $data['seed_source'] ?? 'operator_or_system',
            'algorithm' => $data['algorithm'] ?? 'sha256(seed:participant)',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'location' => $data['location'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'public_notice_text' => $data['public_notice_text'] ?? null,
        ]);

        $draw->forceFill([
            'status' => LotteryDrawStatus::Draft,
            'started_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::CREATE, $draw, 'allocations', 'lottery_draw_create', 'Sorteio auditável criado.');

        return $draw->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LotteryDraw $draw, array $data, User $actor): LotteryDraw
    {
        if (in_array($draw->status, [LotteryDrawStatus::Completed, LotteryDrawStatus::Validated], true)) {
            throw ValidationException::withMessages(['lottery_draw' => 'Sorteios concluídos ou validados não podem ser editados.']);
        }

        $draw->fill($data);
        $draw->save();

        $this->audit->record(AuditEvents::UPDATE, $draw, 'allocations', 'lottery_draw_update', 'Sorteio auditável atualizado.');

        return $draw->refresh();
    }

    public function run(LotteryDraw $draw, User $actor, ?string $seed = null): LotteryDraw
    {
        return DB::transaction(function () use ($draw, $seed): LotteryDraw {
            $draw = LotteryDraw::query()
                ->with('participants')
                ->lockForUpdate()
                ->findOrFail($draw->id);

            if ($draw->status === LotteryDrawStatus::Validated) {
                throw ValidationException::withMessages(['lottery_draw' => 'Sorteio validado não pode ser reexecutado.']);
            }

            if ($draw->participants_locked_at === null) {
                throw ValidationException::withMessages(['participants' => 'Bloqueie os participantes antes de executar o sorteio.']);
            }

            $participants = $draw->participants
                ->where('is_eligible', true)
                ->filter(fn ($participant): bool => $participant->status->value !== 'excluded')
                ->values();

            if ($participants->isEmpty()) {
                throw ValidationException::withMessages(['participants' => 'Não existem participantes elegíveis para sortear.']);
            }

            LotteryResult::query()
                ->where('lottery_run_id', $draw->id)
                ->delete();

            $execution = $this->engine->draw($participants, $seed ?? $draw->seed, $draw->algorithm ?? 'sha256(seed:participant)');
            $allocationMap = $draw->allocationRun?->allocations()->get()->keyBy('application_id') ?? collect();

            foreach ($execution['ordered'] as $item) {
                $selected = $item['drawn_position'] === 1;
                $allocation = $allocationMap->get($item['application_id']);

                $result = new LotteryResult([
                    'lottery_run_id' => $draw->id,
                    'lottery_participant_id' => $item['participant_id'],
                    'application_id' => $item['application_id'],
                    'user_id' => $item['user_id'],
                    'selected' => $selected,
                    'assigned_contest_housing_unit_id' => $allocation?->contest_housing_unit_id,
                    'assigned_housing_unit_id' => $allocation?->housing_unit_id,
                ]);

                $result->forceFill([
                    'draw_order' => $item['drawn_position'],
                    'result_type' => $selected ? LotteryResultType::Selected : LotteryResultType::Reserve,
                    'status' => LotteryResultStatus::Generated,
                    'random_value' => $item['random_value'],
                    'result_hash' => hash('sha256', json_encode($item, JSON_THROW_ON_ERROR)),
                    'audit_data' => [
                        'participant_number' => $item['participant_number'],
                        'algorithm' => $execution['algorithm'],
                    ],
                ])->save();
            }

            $resultHash = $execution['result_hash'];
            $participantsHash = $draw->participants_hash ?? $this->snapshots->hashParticipants($participants);

            $draw->forceFill([
                'status' => LotteryDrawStatus::Completed,
                'seed' => $execution['seed'],
                'seed_hash' => $execution['seed_hash'],
                'result_hash' => $resultHash,
                'participants_hash' => $participantsHash,
                'drawn_count' => count($execution['ordered']),
                'started_at' => $draw->started_at ?? now(),
                'completed_at' => now(),
                'audit_hash' => $resultHash,
                'audit_payload' => [
                    'participants_hash' => $participantsHash,
                    'seed_hash' => $execution['seed_hash'],
                    'result_hash' => $resultHash,
                    'algorithm' => $execution['algorithm'],
                ],
            ])->save();

            $this->audit->record(AuditEvents::CREATE, $draw, 'allocations', 'lottery_draw_run', 'Sorteio auditável executado.', metadata: [
                'result_hash' => $resultHash,
            ]);

            return $draw->refresh()->load(['participants', 'results']);
        });
    }

    public function validateResult(LotteryDraw $draw, User $actor): LotteryDraw
    {
        return DB::transaction(function () use ($draw, $actor): LotteryDraw {
            $draw = LotteryDraw::query()->with('results')->lockForUpdate()->findOrFail($draw->id);

            if ($draw->status !== LotteryDrawStatus::Completed) {
                throw ValidationException::withMessages(['lottery_draw' => 'Só sorteios concluídos podem ser validados.']);
            }

            $draw->forceFill([
                'status' => LotteryDrawStatus::Validated,
                'validated_at' => now(),
                'validated_by' => $actor->id,
            ])->save();

            LotteryResult::query()
                ->where('lottery_run_id', $draw->id)
                ->update([
                    'status' => LotteryResultStatus::Validated->value,
                    'validated_at' => now(),
                    'validated_by' => $actor->id,
                ]);

            $this->audit->record(AuditEvents::APPROVE, $draw, 'allocations', 'lottery_draw_validate', 'Resultado de sorteio validado.');

            return $draw->refresh()->load('results');
        });
    }

    public function cancel(LotteryDraw $draw, User $actor, string $reason): LotteryDraw
    {
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Indique o motivo de cancelamento.']);
        }

        $draw->forceFill([
            'status' => LotteryDrawStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancellation_reason' => $reason,
        ])->save();

        $this->audit->record(AuditEvents::UPDATE, $draw, 'allocations', 'lottery_draw_cancel', 'Sorteio cancelado.', metadata: [
            'reason' => $reason,
        ]);

        return $draw->refresh();
    }
}
