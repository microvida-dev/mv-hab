<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Data\DocumentIntelligence\DocumentAiSuggestionDraft;
use App\Enums\DocumentAiSuggestionStatus;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class DocumentAiAssistantPersister
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     * @param  list<DocumentAiSuggestionDraft>  $suggestions
     */
    public function persist(DocumentAiAnalysis $analysis, DocumentAiScoreResult $result, array $flags, array $suggestions, ?User $actor = null): DocumentAiScore
    {
        return DB::transaction(function () use ($analysis, $result, $flags, $suggestions, $actor): DocumentAiScore {
            $application = $this->resolveApplication($analysis);
            $score = DocumentAiScore::query()->firstOrNew([
                'document_ai_analysis_id' => $analysis->id,
            ]);
            $score->forceFill([
                'document_ai_analysis_id' => $analysis->id,
                'document_submission_id' => $analysis->document_submission_id,
                'application_id' => $application?->id,
                'score' => $result->score,
                'label' => $result->label,
                'components' => $result->components,
                'explanation' => $result->explanation,
                'summary' => $result->summary,
                'requires_manual_review' => $result->requiresManualReview,
                'calculated_at' => now(),
            ]);
            $score->save();

            foreach ($flags as $flag) {
                $this->persistFlag($analysis, $flag);
            }

            foreach ($suggestions as $suggestion) {
                $this->persistSuggestion($analysis, $score, $application, $suggestion);
            }

            $this->recordProcessingLog($analysis, 'assistant_score_persisted', 'info', 'Score IA e sugestões de aperfeiçoamento persistidos.', [
                'score_id' => $score->id,
                'score' => $result->score,
                'label' => $result->label->value,
                'flags_count' => count($flags),
                'suggestions_count' => count($suggestions),
            ]);

            $this->auditLogger->record(
                event: AuditEvents::UPDATE,
                auditable: $score,
                module: 'documents',
                action: 'document_ai_score_calculated',
                description: 'Score de confiança IA calculado para apoio à revisão documental.',
                metadata: [
                    'document_ai_analysis_id' => $analysis->id,
                    'application_id' => $application?->id,
                    'score' => $result->score,
                    'label' => $result->label->value,
                    'requires_manual_review' => $result->requiresManualReview,
                    'actor_id' => $actor?->id,
                ],
            );

            return $score;
        });
    }

    private function persistFlag(DocumentAiAnalysis $analysis, DocumentAiRiskFlag $flag): void
    {
        $model = DocumentAiFlag::query()->firstOrNew([
            'document_ai_analysis_id' => $analysis->id,
            'code' => $flag->code->value,
        ]);

        $model->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'code' => $flag->code->value,
            'severity' => $flag->severity->value,
            'message' => $flag->message,
            'score_impact' => $flag->scoreImpact,
            'suggestion_template' => $flag->suggestionTemplate,
            'detected_by' => $flag->detectedBy,
            'confidence' => $flag->confidence,
            'details' => [
                'label' => $flag->code->label(),
                ...$flag->metadata,
            ],
            'requires_manual_review' => $flag->requiresManualReview,
        ]);
        $model->save();
    }

    private function persistSuggestion(DocumentAiAnalysis $analysis, DocumentAiScore $score, ?Application $application, DocumentAiSuggestionDraft $draft): void
    {
        $suggestion = DocumentAiSuggestion::query()->firstOrNew([
            'document_ai_analysis_id' => $analysis->id,
            'flag_code' => $draft->flagCode->value,
        ]);

        $finalStatus = $suggestion->exists && in_array($suggestion->status, [
            DocumentAiSuggestionStatus::Accepted,
            DocumentAiSuggestionStatus::Dismissed,
            DocumentAiSuggestionStatus::Sent,
        ], true)
            ? $suggestion->status
            : $draft->status;

        $suggestion->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'document_ai_score_id' => $score->id,
            'application_id' => $application?->id,
            'flag_code' => $draft->flagCode->value,
            'severity' => $draft->severity,
            'status' => $finalStatus,
            'suggestion' => $suggestion->exists && $suggestion->status === DocumentAiSuggestionStatus::Edited
                ? $suggestion->suggestion
                : $draft->suggestion,
            'metadata' => $draft->metadata,
        ]);
        $suggestion->save();

        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $suggestion,
            module: 'documents',
            action: 'document_ai_suggestion_created',
            description: 'Sugestão IA documental criada para revisão humana.',
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_ai_score_id' => $score->id,
                'application_id' => $application?->id,
                'flag_code' => $suggestion->flag_code,
                'severity' => $suggestion->severity->value,
                'status' => $suggestion->status->value,
            ],
        );
    }

    private function resolveApplication(DocumentAiAnalysis $analysis): ?Application
    {
        $analysis->loadMissing([
            'documentSubmission.application',
            'documentSubmission.applications',
            'documentSubmission.adhesionRegistration.applications',
        ]);

        $submission = $analysis->documentSubmission;

        if (! $submission instanceof DocumentSubmission) {
            return null;
        }

        if ($submission->application instanceof Application) {
            return $submission->application;
        }

        $application = $submission->applications->sortByDesc('created_at')->first();

        if ($application instanceof Application) {
            return $application;
        }

        return $submission->adhesionRegistration?->applications->sortByDesc('created_at')->first();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recordProcessingLog(DocumentAiAnalysis $analysis, string $step, string $level, string $message, array $context): void
    {
        $log = new DocumentAiProcessingLog([
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'created_at' => now(),
        ]);

        $log->document_ai_analysis_id = $analysis->id;
        $log->save();
    }
}
