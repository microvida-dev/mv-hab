<?php

namespace App\Services\ContestClosure;

use App\Enums\KeyHandoverStatus;
use App\Enums\LotteryDrawStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\Contest;
use App\Models\KeyHandoverAppointment;
use App\Models\LotteryDraw;
use App\Models\PostDrawReport;
use App\Models\TenantTransition;
use App\Models\WinnerRegistration;

class ContestClosureValidator
{
    /**
     * @return list<string>
     */
    public function criticalPendingItems(Contest $contest): array
    {
        $pending = [];

        $hasUnvalidatedDraw = LotteryDraw::query()
            ->where('contest_id', $contest->id)
            ->whereIn('status', [
                LotteryDrawStatus::ParticipantsLoaded->value,
                LotteryDrawStatus::ParticipantsLocked->value,
                LotteryDrawStatus::Ready->value,
                LotteryDrawStatus::Running->value,
                LotteryDrawStatus::Completed->value,
            ])
            ->exists();

        if ($hasUnvalidatedDraw) {
            $pending[] = 'Existe sorteio por validar.';
        }

        $hasValidatedDraw = LotteryDraw::query()
            ->where('contest_id', $contest->id)
            ->where('status', LotteryDrawStatus::Validated->value)
            ->exists();

        if ($hasValidatedDraw && ! WinnerRegistration::query()->whereHas('lotteryDraw', fn ($query) => $query->where('contest_id', $contest->id))->exists()) {
            $pending[] = 'Existe sorteio validado sem vencedor registado.';
        }

        if ($hasValidatedDraw && ! PostDrawReport::query()->where('contest_id', $contest->id)->exists()) {
            $pending[] = 'Relatório pós-sorteio ainda não gerado.';
        }

        $winnerIds = WinnerRegistration::query()
            ->whereHas('lotteryDraw', fn ($query) => $query->where('contest_id', $contest->id))
            ->pluck('id');

        if ($winnerIds->isNotEmpty()) {
            $hasMissingOrPendingKeys = ! KeyHandoverAppointment::query()
                ->whereIn('winner_registration_id', $winnerIds)
                ->where('status', KeyHandoverStatus::Completed->value)
                ->exists();

            if ($hasMissingOrPendingKeys) {
                $pending[] = 'Entrega de chaves pendente.';
            }

            $hasCompletedTransition = TenantTransition::query()
                ->whereIn('winner_registration_id', $winnerIds)
                ->where('status', TenantTransitionStatus::Completed->value)
                ->exists();

            if (! $hasCompletedTransition) {
                $pending[] = 'Transição para área do inquilino pendente.';
            }
        }

        return $pending;
    }
}
