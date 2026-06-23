<?php

namespace App\Services\Scoring;

use App\Enums\ScoreCriterionResult;
use App\Models\ApplicationScore;
use App\Models\ApplicationScoreDetail;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ManualScoreService
{
    public function __construct(
        private readonly ApplicationScoreService $scoreService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @return Collection<int, ApplicationScoreDetail>
     */
    public function pending(ApplicationScore $score): Collection
    {
        return $score->details()
            ->where('requires_manual_review', true)
            ->whereNull('reviewed_at')
            ->get();
    }

    public function updateManualScore(
        ApplicationScore $score,
        int $detailId,
        float $points,
        ?string $notes,
        User $actor,
    ): ApplicationScore {
        if ($score->isLocked()) {
            throw ValidationException::withMessages([
                'application_score' => 'A pontuação está bloqueada e não pode ser alterada.',
            ]);
        }

        /** @var ApplicationScoreDetail $detail */
        $detail = $score->details()
            ->where('requires_manual_review', true)
            ->whereKey($detailId)
            ->firstOrFail();

        $maxPoints = $detail->max_points === null ? null : (float) $detail->max_points;

        if ($maxPoints !== null && $points > $maxPoints) {
            throw ValidationException::withMessages([
                'manual_points' => 'A pontuação manual não pode exceder a pontuação máxima do critério.',
            ]);
        }

        $oldValues = [
            'manual_points' => $detail->manual_points,
            'manual_notes' => $detail->manual_notes,
        ];

        $detail->forceFill([
            'result' => ScoreCriterionResult::Manual,
            'points_awarded' => $points,
            'manual_points' => $points,
            'manual_notes' => $notes,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ])->save();

        $updated = $this->scoreService->recalculateTotals($score->refresh());

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $detail,
            module: 'scoring',
            action: 'manual_score_update',
            description: 'Pontuação manual de critério de classificação atualizada.',
            oldValues: $oldValues,
            newValues: [
                'manual_points' => $points,
                'manual_notes_present' => filled($notes),
            ],
            metadata: [
                'actor_id' => $actor->id,
                'application_score_id' => $score->id,
            ],
        );

        return $updated;
    }
}
