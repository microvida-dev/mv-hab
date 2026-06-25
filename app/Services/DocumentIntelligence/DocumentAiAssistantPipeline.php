<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Events\DocumentAiManualReviewRecommended;
use App\Events\DocumentAiRiskFlagDetected;
use App\Events\DocumentAiScoreCalculated;
use App\Events\DocumentAiScoreCalculationFailed;
use App\Events\DocumentAiScoreCalculationStarted;
use App\Events\DocumentAiSuggestionGenerated;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Throwable;

class DocumentAiAssistantPipeline
{
    public function __construct(
        private readonly DocumentRiskFlagDetector $flagDetector,
        private readonly DocumentAiScoreCalculator $calculator,
        private readonly DocumentAiScoreExplainer $explainer,
        private readonly DocumentSuggestionGenerator $suggestionGenerator,
        private readonly DocumentAiAssistantPersister $persister,
        private readonly DocumentAiWorkTaskService $workTasks,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function process(DocumentAiAnalysis $analysis, ?User $actor = null): DocumentAiScore
    {
        if (! (bool) config('document-ai-score.enabled', true)) {
            throw new \RuntimeException('O cálculo de score IA está desativado por configuração.');
        }

        event(new DocumentAiScoreCalculationStarted((int) $analysis->id));
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_score_calculation_started',
            description: 'Cálculo de score IA iniciado.',
            metadata: ['document_ai_analysis_id' => $analysis->id],
        );
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $analysis,
            module: 'documents',
            action: 'document_ai_validation_started',
            description: 'Validação assistiva IA documental iniciada.',
            metadata: ['document_ai_analysis_id' => $analysis->id],
        );

        try {
            $flags = $this->flagDetector->detect($analysis);
            $baseResult = $this->calculator->calculate($analysis, $flags);
            $explanation = $this->explainer->explain($baseResult, $flags);
            $result = new DocumentAiScoreResult(
                score: $baseResult->score,
                label: $baseResult->label,
                components: $baseResult->components,
                summary: $baseResult->summary,
                explanation: $explanation,
                requiresManualReview: $baseResult->requiresManualReview,
            );
            $suggestions = $this->suggestionGenerator->generate($flags);
            $score = $this->persister->persist($analysis, $result, $flags, $suggestions, $actor);
            $task = $this->workTasks->createForRiskScore($analysis, $score, $flags, $actor);

            foreach ($flags as $flag) {
                event(new DocumentAiRiskFlagDetected((int) $analysis->id, $flag->code, $flag->severity, $flag->scoreImpact));
            }

            foreach ($suggestions as $suggestion) {
                event(new DocumentAiSuggestionGenerated((int) $analysis->id, $suggestion->flagCode, $suggestion->status));
            }

            event(new DocumentAiScoreCalculated((int) $analysis->id, (int) $score->id, $result->score, $result->label, $result->requiresManualReview));
            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $analysis,
                module: 'documents',
                action: 'document_ai_validation_completed',
                description: 'Validação assistiva IA documental concluída.',
                metadata: [
                    'document_ai_analysis_id' => $analysis->id,
                    'document_ai_score_id' => $score->id,
                    'flags_count' => count($flags),
                    'suggestions_count' => count($suggestions),
                    'work_task_id' => $task?->id,
                    'requires_manual_review' => $result->requiresManualReview,
                ],
            );

            if ($result->requiresManualReview) {
                event(new DocumentAiManualReviewRecommended((int) $analysis->id, (int) $score->id, $result->score, $result->label));
                $this->auditLogger->record(
                    event: AuditEvents::UPDATE,
                    auditable: $analysis,
                    module: 'documents',
                    action: 'document_ai_manual_review_required',
                    description: 'IA documental recomendou revisão manual.',
                    metadata: [
                        'document_ai_analysis_id' => $analysis->id,
                        'document_ai_score_id' => $score->id,
                        'score' => $result->score,
                        'label' => $result->label->value,
                        'work_task_id' => $task?->id,
                    ],
                );
            }

            return $score;
        } catch (Throwable $exception) {
            event(new DocumentAiScoreCalculationFailed((int) $analysis->id, class_basename($exception)));
            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $analysis,
                module: 'documents',
                action: 'document_ai_score_calculation_failed',
                description: 'Falha controlada no cálculo de score IA.',
                metadata: [
                    'document_ai_analysis_id' => $analysis->id,
                    'failure' => class_basename($exception),
                ],
            );

            throw $exception;
        }
    }
}
