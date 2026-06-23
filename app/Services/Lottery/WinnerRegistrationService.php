<?php

namespace App\Services\Lottery;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryParticipantStatus;
use App\Enums\LotteryResultStatus;
use App\Models\Allocation;
use App\Models\LotteryResult;
use App\Models\User;
use App\Models\WinnerRegistration;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WinnerRegistrationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(LotteryResult $result, User $actor, array $data = []): WinnerRegistration
    {
        return DB::transaction(function () use ($result, $actor, $data): WinnerRegistration {
            $result = LotteryResult::query()
                ->with(['lotteryDraw', 'lotteryParticipant'])
                ->lockForUpdate()
                ->findOrFail($result->id);

            if ($result->lotteryDraw->status !== LotteryDrawStatus::Validated || $result->status !== LotteryResultStatus::Validated) {
                throw ValidationException::withMessages(['lottery_result' => 'O vencedor só pode ser registado a partir de resultado validado.']);
            }

            if (! $result->selected) {
                throw ValidationException::withMessages(['lottery_result' => 'Apenas o resultado vencedor pode ser registado como vencedor.']);
            }

            if ($result->assigned_housing_unit_id !== null) {
                $hasWinner = WinnerRegistration::query()
                    ->where('housing_unit_id', $result->assigned_housing_unit_id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->exists();

                if ($hasWinner) {
                    throw ValidationException::withMessages(['housing_unit_id' => 'Já existe vencedor ativo para esta habitação.']);
                }
            }

            $allocation = Allocation::query()
                ->where('application_id', $result->application_id)
                ->where(function ($query) use ($result): void {
                    $query->whereNull('housing_unit_id')
                        ->orWhere('housing_unit_id', $result->assigned_housing_unit_id);
                })
                ->latest()
                ->first();

            $winner = new WinnerRegistration([
                'lottery_run_id' => $result->lottery_run_id,
                'lottery_draw_result_id' => $result->id,
                'allocation_id' => $allocation?->id,
                'application_id' => $result->application_id,
                'user_id' => $result->user_id,
                'contest_housing_unit_id' => $result->assigned_contest_housing_unit_id,
                'housing_unit_id' => $result->assigned_housing_unit_id,
                'validation_notes' => $data['validation_notes'] ?? null,
                'metadata' => ['source' => 'validated_lottery_result'],
            ]);

            $winner->forceFill([
                'status' => 'active',
                'registered_at' => now(),
                'registered_by' => $actor->id,
            ])->save();

            $result->lotteryParticipant?->forceFill([
                'status' => LotteryParticipantStatus::Winner,
            ])->save();

            $this->audit->record(AuditEvents::APPROVE, $winner, 'allocations', 'winner_registration_create', 'Vencedor de sorteio registado.');

            return $winner->refresh();
        });
    }
}
