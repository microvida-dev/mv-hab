<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentValidationCheckResult;
use App\Enums\DocumentAiValidationSeverity;
use App\Events\DocumentCandidateCriticalDivergenceDetected;
use App\Events\DocumentCandidateValidationCompleted;
use App\Events\DocumentCandidateValidationFailed;
use App\Events\DocumentCandidateValidationRequiresReview;
use App\Events\DocumentCandidateValidationStarted;
use App\Jobs\CalculateDocumentAiScoreJob;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiValidation;
use App\Models\DocumentAiValidationRun;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Throwable;

class DocumentCandidateValidationPipeline
{
    public function __construct(
        private readonly CandidateDeclaredDataResolver $declaredDataResolver,
        private readonly ExtractedDocumentDataResolver $extractedDataResolver,
        private readonly DocumentValidationRuleRegistry $ruleRegistry,
        private readonly DocumentValidationComparator $comparator,
        private readonly DocumentValidationSeverityResolver $severityResolver,
        private readonly DocumentValidationPersister $persister,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function processAnalysis(DocumentAiAnalysis $analysis, ?Application $application = null, ?User $actor = null): DocumentAiValidationRun
    {
        if (! (bool) config('document-ai-validation.enabled', true)) {
            throw new \RuntimeException('A validação documental contra candidatura está desativada por configuração.');
        }

        $application ??= $this->resolveApplication($analysis);

        if (! $application instanceof Application) {
            event(new DocumentCandidateValidationFailed(null, null, (int) $analysis->id, 'application_not_found'));
            throw new \RuntimeException('Não foi possível resolver a candidatura associada ao documento.');
        }

        $run = $this->persister->startRun($application, $actor);
        event(new DocumentCandidateValidationStarted($run->id, (int) $application->id, (int) $analysis->id));
        $this->auditStart($run, $analysis);

        try {
            $validations = $this->validateAnalysisIntoRun($run, $analysis, $application);
            $completedRun = $this->persister->completeRun($run, $validations);
            $this->auditComplete($completedRun, $analysis);
            $this->dispatchCompletionEvents($completedRun, $validations);
            $this->dispatchScoreJob($analysis);

            return $completedRun;
        } catch (Throwable $exception) {
            $failedRun = $this->persister->failRun($run, $this->controlledFailureReason($exception, 'Falha técnica controlada no cruzamento documental.'));
            $this->auditFailure($failedRun, $analysis, $exception);
            event(new DocumentCandidateValidationFailed($failedRun->id, (int) $application->id, (int) $analysis->id, $this->failureCode($exception)));

            return $failedRun;
        }
    }

    public function processApplication(Application $application, ?User $actor = null): DocumentAiValidationRun
    {
        if (! (bool) config('document-ai-validation.enabled', true)) {
            throw new \RuntimeException('A validação documental contra candidatura está desativada por configuração.');
        }

        $run = $this->persister->startRun($application, $actor);
        event(new DocumentCandidateValidationStarted($run->id, (int) $application->id));
        $this->auditStart($run);

        try {
            $validations = [];
            $analyses = [];

            foreach ($this->analysesForApplication($application) as $analysis) {
                array_push($validations, ...$this->validateAnalysisIntoRun($run, $analysis, $application));
                $analyses[] = $analysis;
            }

            $completedRun = $this->persister->completeRun($run, $validations);
            $this->auditComplete($completedRun);
            $this->dispatchCompletionEvents($completedRun, $validations);

            foreach ($analyses as $analysis) {
                $this->dispatchScoreJob($analysis);
            }

            return $completedRun;
        } catch (Throwable $exception) {
            $failedRun = $this->persister->failRun($run, $this->controlledFailureReason($exception, 'Falha técnica controlada no reprocessamento da candidatura.'));
            $this->auditFailure($failedRun, null, $exception);
            event(new DocumentCandidateValidationFailed($failedRun->id, (int) $application->id, null, $this->failureCode($exception)));

            return $failedRun;
        }
    }

    private function dispatchScoreJob(DocumentAiAnalysis $analysis): void
    {
        if (! (bool) config('document-ai-score.enabled', true)) {
            return;
        }

        CalculateDocumentAiScoreJob::dispatch((int) $analysis->id)
            ->onQueue((string) config('document-ai-score.queue', 'default'));
    }

    /**
     * @return list<DocumentAiValidation>
     */
    private function validateAnalysisIntoRun(DocumentAiValidationRun $run, DocumentAiAnalysis $analysis, Application $application): array
    {
        $declared = $this->declaredDataResolver->resolve($application);
        $extracted = $this->extractedDataResolver->resolve($analysis);
        $rules = $this->ruleRegistry->rulesFor($extracted->documentType);
        $results = [];

        foreach ($rules as $rule) {
            $candidateValue = $declared->value($rule->candidatePath);
            $extractedValue = $extracted->value($rule->extractedPath);
            $comparison = $this->comparator->compare($candidateValue, $extractedValue, $rule->method);
            $severity = $this->severityResolver->resolve($rule, $comparison['status'], $comparison['metadata']);
            $fieldMetadata = $extracted->metadata($rule->extractedFieldKey());
            $requiresReview = $severity !== DocumentAiValidationSeverity::None
                || (bool) ($fieldMetadata['requires_review'] ?? false);

            $results[] = new DocumentValidationCheckResult(
                rule: $rule,
                status: $comparison['status'],
                severity: $severity,
                confidence: $this->resultConfidence($comparison['confidence'], $fieldMetadata),
                candidateValue: $candidateValue,
                extractedValue: $extractedValue,
                message: $this->message($rule->message, $comparison['status']->label(), $severity->label()),
                recommendation: $requiresReview ? $rule->recommendation : null,
                requiresManualReview: $requiresReview,
                metadata: [
                    ...$comparison['metadata'],
                    'source' => 'candidate_document_validation',
                    'field_confidence' => $fieldMetadata['confidence'] ?? null,
                    'field_requires_review' => (bool) ($fieldMetadata['requires_review'] ?? false),
                ],
            );
        }

        return $this->persister->persist($run, $analysis, $results);
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
     * @return EloquentCollection<int, DocumentAiAnalysis>
     */
    private function analysesForApplication(Application $application): EloquentCollection
    {
        return DocumentAiAnalysis::query()
            ->whereHas('documentSubmission', function ($query) use ($application): void {
                $query->where('application_id', $application->id)
                    ->orWhereHas('applications', fn ($applications) => $applications->whereKey($application->id));
            })
            ->whereNotNull('detected_document_type')
            ->with(['fields', 'documentSubmission'])
            ->latest()
            ->get();
    }

    /**
     * @param  list<DocumentAiValidation>  $validations
     */
    private function dispatchCompletionEvents(DocumentAiValidationRun $run, array $validations): void
    {
        event(new DocumentCandidateValidationCompleted($run->id, (int) $run->application_id, $run->status, (int) $run->total_checks));

        if ($run->requires_manual_review) {
            event(new DocumentCandidateValidationRequiresReview($run->id, (int) $run->application_id, (int) $run->critical_count, (int) $run->medium_count));
        }

        foreach ($validations as $validation) {
            if ($validation->severity === DocumentAiValidationSeverity::Critical) {
                event(new DocumentCandidateCriticalDivergenceDetected(
                    (int) $validation->id,
                    (int) $validation->application_id,
                    (int) $validation->document_ai_analysis_id,
                    $validation->validation_group->value,
                    $validation->validation_key,
                ));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fieldMetadata
     */
    private function resultConfidence(float $comparisonConfidence, array $fieldMetadata): float
    {
        $fieldConfidence = is_numeric($fieldMetadata['confidence'] ?? null)
            ? (float) $fieldMetadata['confidence']
            : 1.0;

        return round(min($comparisonConfidence, $fieldConfidence), 2);
    }

    private function message(?string $baseMessage, string $statusLabel, string $severityLabel): string
    {
        $message = $baseMessage ?? 'Cruzamento documental concluído.';

        return "{$message} Resultado: {$statusLabel}. Severidade: {$severityLabel}.";
    }

    private function auditStart(DocumentAiValidationRun $run, ?DocumentAiAnalysis $analysis = null): void
    {
        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $run,
            module: 'documents',
            action: 'document_ai_candidate_validation_started',
            description: 'Cruzamento automático entre documento e candidatura iniciado.',
            metadata: [
                'application_id' => $run->application_id,
                'document_ai_analysis_id' => $analysis?->id,
            ],
        );
        $this->auditLogger->record(
            event: AuditEvents::CREATE,
            auditable: $run,
            module: 'documents',
            action: 'document_ai_validation_started',
            description: 'Validação documental assistiva iniciada.',
            metadata: [
                'application_id' => $run->application_id,
                'document_ai_analysis_id' => $analysis?->id,
            ],
        );
    }

    private function auditComplete(DocumentAiValidationRun $run, ?DocumentAiAnalysis $analysis = null): void
    {
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $run,
            module: 'documents',
            action: 'document_ai_candidate_validation_completed',
            description: 'Cruzamento automático entre documento e candidatura concluído.',
            metadata: [
                'application_id' => $run->application_id,
                'document_ai_analysis_id' => $analysis?->id,
                'total_checks' => $run->total_checks,
                'critical_count' => $run->critical_count,
                'medium_count' => $run->medium_count,
                'light_count' => $run->light_count,
                'requires_manual_review' => $run->requires_manual_review,
            ],
        );
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $run,
            module: 'documents',
            action: 'document_ai_validation_completed',
            description: 'Validação documental assistiva concluída.',
            metadata: [
                'application_id' => $run->application_id,
                'document_ai_analysis_id' => $analysis?->id,
                'total_checks' => $run->total_checks,
                'requires_manual_review' => $run->requires_manual_review,
            ],
        );
    }

    private function auditFailure(DocumentAiValidationRun $run, ?DocumentAiAnalysis $analysis, Throwable $exception): void
    {
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $run,
            module: 'documents',
            action: 'document_ai_candidate_validation_failed',
            description: 'Cruzamento automático entre documento e candidatura falhou de forma controlada.',
            metadata: [
                'application_id' => $run->application_id,
                'document_ai_analysis_id' => $analysis?->id,
                'failure_code' => $this->failureCode($exception),
            ],
        );
    }

    private function failureCode(Throwable $exception): string
    {
        return strtolower(str_replace('\\', '_', $exception::class));
    }

    private function controlledFailureReason(Throwable $exception, string $prefix): string
    {
        return $prefix.' Código: '.$this->failureCode($exception).'. Mensagem: '.mb_substr($exception->getMessage(), 0, 240).'.';
    }
}
