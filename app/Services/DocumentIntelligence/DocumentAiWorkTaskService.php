<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskSeverity;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskCreationService;

class DocumentAiWorkTaskService
{
    public function __construct(
        private readonly WorkTaskCreationService $tasks,
        private readonly DocumentAiRiskScoringService $riskScoring,
    ) {}

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    public function createForRiskScore(DocumentAiAnalysis $analysis, DocumentAiScore $score, array $flags, ?User $actor = null): ?WorkTask
    {
        if (! $this->requiresTask($score, $flags)) {
            return null;
        }

        return $this->tasks->createFromSource(
            type: WorkTask::TYPE_DOCUMENT_REVIEW,
            related: $analysis,
            actor: $actor,
            source: 'document_ai_risk:analysis:'.$analysis->id,
            priority: $this->priority($flags),
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_ai_score_id' => $score->id,
                'score' => (int) $score->score,
                'score_label' => $score->label->value,
                'score_colour' => $this->riskScoring->labelForModel($score),
                'requires_manual_review' => (bool) $score->requires_manual_review,
                'risk_flags' => array_values(array_unique(array_map(
                    static fn (DocumentAiRiskFlag $flag): string => $flag->code->value,
                    $flags,
                ))),
                'risk_count' => count($flags),
            ],
        );
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function requiresTask(DocumentAiScore $score, array $flags): bool
    {
        if ((bool) $score->requires_manual_review) {
            return true;
        }

        return collect($flags)->contains(
            fn (DocumentAiRiskFlag $flag): bool => in_array($flag->severity, [
                DocumentAiRiskSeverity::Critical,
                DocumentAiRiskSeverity::High,
            ], true)
        );
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function priority(array $flags): string
    {
        if (collect($flags)->contains(fn (DocumentAiRiskFlag $flag): bool => $flag->severity === DocumentAiRiskSeverity::Critical)) {
            return WorkTask::PRIORITY_URGENT;
        }

        if (collect($flags)->contains(fn (DocumentAiRiskFlag $flag): bool => $flag->severity === DocumentAiRiskSeverity::High)) {
            return WorkTask::PRIORITY_HIGH;
        }

        return WorkTask::PRIORITY_NORMAL;
    }
}
