<?php

namespace App\Services\Lottery;

use App\Enums\RankingUpdateStatus;
use App\Models\LotteryDraw;
use App\Models\RankingUpdateRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class RankingUpdateService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function apply(LotteryDraw $draw, User $actor): RankingUpdateRun
    {
        $draw->loadMissing(['participants', 'results']);

        $before = $draw->participants
            ->sortBy('rank_position')
            ->map(fn ($participant): array => [
                'application_id' => (int) $participant->application_id,
                'rank_position' => $participant->rank_position,
                'score' => $participant->previous_score,
            ])
            ->values()
            ->all();

        $after = $draw->results
            ->sortBy('draw_order')
            ->map(fn ($result): array => [
                'application_id' => (int) $result->application_id,
                'draw_order' => (int) $result->draw_order,
                'result_type' => $result->result_type->value,
            ])
            ->values()
            ->all();

        $run = new RankingUpdateRun([
            'lottery_run_id' => $draw->id,
            'contest_id' => $draw->contest_id,
            'before_snapshot' => $before,
            'after_snapshot' => $after,
            'summary' => [
                'participants' => count($before),
                'results' => count($after),
                'note' => 'Ranking anterior preservado; atualização registada como snapshot pós-sorteio.',
            ],
        ]);

        $run->forceFill([
            'status' => RankingUpdateStatus::Applied,
            'applied_at' => now(),
            'applied_by' => $actor->id,
        ])->save();

        $this->audit->record(AuditEvents::UPDATE, $run, 'scoring', 'ranking_update_after_draw', 'Atualização de ranking pós-sorteio registada.');

        return $run->refresh();
    }
}
