<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationScoreStatus;
use App\Enums\ScoreCriterionResult;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\ApplicationScoreDetail;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Validation\ValidationException;

class ApplicationScoreService
{
    public function __construct(
        private readonly ScoringDataProvider $dataProvider,
        private readonly ScoringCriterionEvaluator $evaluator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function scoreApplication(
        ScoringRun $run,
        Application $application,
        ScoringRuleSet $ruleSet,
        User $actor,
    ): ApplicationScore {
        $ruleSet->loadMissing('criteria.rules');
        $context = $this->dataProvider->forApplication($application);

        $score = new ApplicationScore;
        $score->forceFill([
            'scoring_run_id' => $run->id,
            'application_id' => $application->id,
            'scoring_rule_set_id' => $ruleSet->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $application->user_id,
            'status' => ApplicationScoreStatus::Pending,
            'calculated_by' => $actor->id,
            'calculated_at' => now(),
        ])->save();

        $requiresManualReview = false;
        $excluded = false;
        $exclusionReasons = [];

        foreach ($ruleSet->criteria->where('is_active', true) as $criterion) {
            $evaluation = $this->evaluator->evaluate($criterion, $context);
            $requiresManualReview = $requiresManualReview || $evaluation['requires_manual_review'];

            if ($criterion->is_exclusionary && $evaluation['result'] !== ScoreCriterionResult::Applied) {
                $excluded = true;
                $exclusionReasons[] = $criterion->code;
            }

            $detail = new ApplicationScoreDetail;
            $detail->forceFill([
                'application_score_id' => $score->id,
                'scoring_criterion_id' => $criterion->id,
                'scoring_rule_id' => $evaluation['scoring_rule_id'],
                'code' => $criterion->code,
                'name' => $criterion->name,
                'category' => $criterion->category,
                'result' => $evaluation['result'],
                'points_awarded' => $evaluation['points_awarded'],
                'max_points' => $criterion->max_points ?? $criterion->points,
                'weight' => $criterion->weight,
                'raw_value' => $evaluation['raw_value'],
                'normalized_value' => $evaluation['normalized_value'],
                'message' => $evaluation['message'],
                'technical_message' => $evaluation['technical_message'],
                'requires_manual_review' => $evaluation['requires_manual_review'],
            ])->save();
        }

        return $this->recalculateTotals(
            $score->refresh(),
            excluded: $excluded,
            exclusionReason: $excluded ? 'Critérios exclusionary sem aplicação: '.implode(', ', $exclusionReasons) : null,
        );
    }

    public function recalculateTotals(
        ApplicationScore $score,
        bool $excluded = false,
        ?string $exclusionReason = null,
    ): ApplicationScore {
        if ($score->isLocked()) {
            throw ValidationException::withMessages([
                'application_score' => 'A pontuação está bloqueada e não pode ser recalculada.',
            ]);
        }

        $score->loadMissing('details');

        $automaticScore = (float) $score->details
            ->where('requires_manual_review', false)
            ->sum('points_awarded');
        $manualScore = (float) $score->details
            ->where('requires_manual_review', true)
            ->whereNotNull('reviewed_at')
            ->sum('manual_points');
        $hasManual = $score->details->where('requires_manual_review', true)->isNotEmpty();
        $pendingManual = $score->details
            ->where('requires_manual_review', true)
            ->whereNull('reviewed_at')
            ->isNotEmpty();

        $status = match (true) {
            $excluded || $score->excluded_from_ranking => ApplicationScoreStatus::ExcludedFromScoring,
            $pendingManual => ApplicationScoreStatus::RequiresManualReview,
            $hasManual => ApplicationScoreStatus::ManualReviewCompleted,
            default => ApplicationScoreStatus::Calculated,
        };

        $score->forceFill([
            'status' => $status,
            'automatic_score' => $automaticScore,
            'manual_score' => $manualScore,
            'total_score' => $automaticScore + $manualScore,
            'requires_manual_review' => $pendingManual,
            'excluded_from_ranking' => $excluded || $score->excluded_from_ranking,
            'exclusion_reason' => $exclusionReason ?? $score->exclusion_reason,
        ])->save();

        return $score->refresh();
    }

    public function lock(ApplicationScore $score, User $actor): ApplicationScore
    {
        if ($score->isLocked()) {
            return $score;
        }

        $score->forceFill([
            'status' => ApplicationScoreStatus::Locked,
            'locked_at' => now(),
            'locked_by' => $actor->id,
        ])->save();

        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $score,
            module: 'scoring',
            action: 'application_score_lock',
            description: 'Pontuação de candidatura bloqueada.',
            metadata: ['actor_id' => $actor->id],
        );

        return $score->refresh();
    }
}
