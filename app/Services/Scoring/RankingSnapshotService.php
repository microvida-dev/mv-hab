<?php

namespace App\Services\Scoring;

use App\Enums\RankingEntryStatus;
use App\Enums\RankingSnapshotStatus;
use App\Models\ApplicationScore;
use App\Models\RankingEntry;
use App\Models\RankingSnapshot;
use App\Models\ScoringRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Collection;

class RankingSnapshotService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @param  Collection<int, ApplicationScore>  $scores
     */
    public function createForRun(ScoringRun $run, Collection $scores, User $actor): RankingSnapshot
    {
        $previousSnapshot = RankingSnapshot::query()
            ->where('program_id', $run->program_id)
            ->where('contest_id', $run->contest_id)
            ->latest('snapshot_number')
            ->first();
        $snapshotNumber = ((int) ($previousSnapshot->snapshot_number ?? 0)) + 1;

        $snapshot = new RankingSnapshot;
        $snapshot->forceFill([
            'scoring_run_id' => $run->id,
            'program_id' => $run->program_id,
            'contest_id' => $run->contest_id,
            'snapshot_number' => $snapshotNumber,
            'status' => RankingSnapshotStatus::Internal,
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'published_at' => null,
            'notes' => 'Snapshot interno gerado pela Sprint 10. Não publicado.',
        ])->save();

        foreach ($scores as $score) {
            $previousRank = $previousSnapshot
                ? $previousSnapshot->entries()->where('application_id', $score->application_id)->value('rank_position')
                : null;

            $entry = new RankingEntry;
            $entry->forceFill([
                'ranking_snapshot_id' => $snapshot->id,
                'application_score_id' => $score->id,
                'application_id' => $score->application_id,
                'rank_position' => $score->rank_position,
                'previous_rank_position' => $previousRank,
                'total_score' => $score->total_score,
                'tie_breaker_values' => $score->tie_breaker_values,
                'is_tied' => $score->is_tied,
                'status' => $this->entryStatus($score),
            ])->save();
        }

        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $snapshot,
            module: 'scoring',
            action: 'ranking_snapshot_create',
            description: 'Snapshot interno de ranking gerado.',
            metadata: [
                'actor_id' => $actor->id,
                'scoring_run_id' => $run->id,
                'entries_count' => $scores->count(),
            ],
        );

        return $snapshot->load('entries');
    }

    public function lock(RankingSnapshot $snapshot, User $actor): RankingSnapshot
    {
        $snapshot->forceFill(['status' => RankingSnapshotStatus::Locked])->save();
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $snapshot,
            module: 'scoring',
            action: 'ranking_snapshot_lock',
            description: 'Snapshot interno de ranking bloqueado.',
            metadata: ['actor_id' => $actor->id],
        );

        return $snapshot->refresh();
    }

    public function archive(RankingSnapshot $snapshot, User $actor): RankingSnapshot
    {
        $snapshot->forceFill(['status' => RankingSnapshotStatus::Archived])->save();
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $snapshot,
            module: 'scoring',
            action: 'ranking_snapshot_archive',
            description: 'Snapshot interno de ranking arquivado.',
            metadata: ['actor_id' => $actor->id],
        );

        return $snapshot->refresh();
    }

    private function entryStatus(ApplicationScore $score): RankingEntryStatus
    {
        if ($score->excluded_from_ranking) {
            return RankingEntryStatus::Excluded;
        }

        if ($score->requires_manual_review) {
            return RankingEntryStatus::RequiresManualReview;
        }

        return $score->is_tied ? RankingEntryStatus::Tied : RankingEntryStatus::Ranked;
    }
}
