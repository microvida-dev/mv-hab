<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionResult;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiStatus;
use App\Events\DocumentFieldExtractionCompleted;
use App\Events\DocumentFieldExtractionFailed;
use App\Events\DocumentFieldExtractionRequiresReview;
use App\Events\DocumentFieldExtractionStarted;
use App\Jobs\ValidateDocumentAiAgainstApplicationJob;
use App\Models\Application;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Models\DocumentSubmission;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Throwable;

class DocumentFieldExtractionPipeline
{
    public function __construct(
        private readonly DocumentExtractionSchemaRegistry $schemaRegistry,
        private readonly RegexFieldExtractor $regexExtractor,
        private readonly LocalAiFieldExtractor $localAiExtractor,
        private readonly DocumentExtractionResultValidator $validator,
        private readonly DocumentExtractionScorer $scorer,
        private readonly DocumentExtractionPersister $persister,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function process(DocumentAiAnalysis $analysis): DocumentAiAnalysis
    {
        if (! (bool) config('document-ai-extraction.enabled', true)) {
            $this->log($analysis, 'field_extraction_skipped', 'info', 'Extração estruturada desativada por configuração.');

            return $analysis;
        }

        $documentType = $analysis->detected_document_type;

        if (! $documentType instanceof DocumentAiDocumentType) {
            return $this->markFailed($analysis, 'missing_document_type', 'Tipo documental não disponível para extração.');
        }

        event(new DocumentFieldExtractionStarted($analysis->id, $documentType));
        $this->markProcessing($analysis, $documentType);

        $schema = $this->schemaRegistry->schemaFor($documentType);

        if ($schema === null) {
            return $this->unsupported($analysis, $documentType);
        }

        $ocrText = (string) ($analysis->ocr_text ?? $analysis->raw_text ?? '');

        if (trim($ocrText) === '') {
            return $this->markFailed($analysis, 'empty_ocr_text', 'Texto OCR indisponível para extração.');
        }

        try {
            $regex = $this->regexExtractor->extract($ocrText, $schema);
            $ai = $this->localAiExtractor->extract($ocrText, $schema);
            $merged = $this->mergeFields($regex['fields'], $ai['fields']);
            $flags = [...$regex['flags'], ...$ai['flags']];
            $validated = $this->validator->validate($schema, $merged, $flags);
            $source = $ai['fields'] === [] ? 'regex' : 'regex+local_ai';
            $result = $this->scorer->score($schema, $validated['fields'], $validated['flags'], $source);
            $processed = $this->persister->persist($analysis, $result);

            $this->auditResult($processed, $result);

            if ($result->requiresManualReview) {
                event(new DocumentFieldExtractionRequiresReview($processed->id, $documentType, $result->status, $result->confidence));
            } else {
                event(new DocumentFieldExtractionCompleted($processed->id, $documentType, $result->status, $result->confidence));
            }

            $this->dispatchCandidateValidation($processed);

            return $processed;
        } catch (Throwable $exception) {
            return $this->markFailed($analysis, $this->failureCode($exception), 'Falha técnica controlada na extração estruturada.');
        }
    }

    private function markProcessing(DocumentAiAnalysis $analysis, DocumentAiDocumentType $documentType): void
    {
        $analysis->forceFill([
            'extraction_status' => DocumentAiExtractionStatus::Processing,
            'extraction_schema_version' => (string) config('document-ai-extraction.schema_version', '1.0'),
            'extraction_started_at' => now(),
            'extraction_failed_at' => null,
            'extraction_requires_manual_review' => false,
        ])->save();

        $this->log($analysis, 'field_extraction_started', 'info', 'Extração estruturada iniciada.', [
            'schema_version' => (string) config('document-ai-extraction.schema_version', '1.0'),
            'status' => DocumentAiExtractionStatus::Processing->value,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_field_extraction_started', 'Extração estruturada iniciada.', [
            'document_type' => $documentType->value,
            'status' => DocumentAiExtractionStatus::Processing->value,
        ]);
    }

    private function unsupported(DocumentAiAnalysis $analysis, DocumentAiDocumentType $documentType): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'extraction_status' => DocumentAiExtractionStatus::UnsupportedDocumentType,
            'extraction_schema_version' => (string) config('document-ai-extraction.schema_version', '1.0'),
            'extraction_confidence' => '0.00',
            'extraction_failed_at' => now(),
            'extraction_requires_manual_review' => true,
            'status' => DocumentAiStatus::ManualReview,
            'manual_review_at' => now(),
            'failure_reason' => 'Tipo documental sem schema de extração estruturada.',
        ])->save();

        $this->recordFlag($analysis, 'unsupported_document_type', 'low', 'Tipo documental sem schema de extração estruturada.', [
            'document_type' => $documentType->value,
        ]);
        $this->log($analysis, 'field_extraction_unsupported', 'warning', 'Tipo documental sem schema de extração.', [
            'status' => DocumentAiExtractionStatus::UnsupportedDocumentType->value,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_field_extraction_unsupported', 'Extração estruturada não suportada para este tipo documental.', [
            'document_type' => $documentType->value,
        ]);
        event(new DocumentFieldExtractionFailed($analysis->id, 'unsupported_document_type'));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    private function markFailed(DocumentAiAnalysis $analysis, string $code, string $reason): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'extraction_status' => DocumentAiExtractionStatus::Failed,
            'extraction_confidence' => '0.00',
            'extraction_failed_at' => now(),
            'extraction_requires_manual_review' => true,
            'status' => DocumentAiStatus::ManualReview,
            'manual_review_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        $this->recordFlag($analysis, $code, 'medium', $reason, ['category' => 'structured_extraction']);
        $this->log($analysis, 'field_extraction_failed', 'error', 'Falha controlada na extração estruturada.', [
            'status' => DocumentAiExtractionStatus::Failed->value,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_field_extraction_failed', 'Extração estruturada falhou de forma controlada.', [
            'failure_code' => $code,
        ]);
        event(new DocumentFieldExtractionFailed($analysis->id, $code));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    /**
     * @param  list<ExtractedDocumentField>  $regexFields
     * @param  list<ExtractedDocumentField>  $aiFields
     * @return list<ExtractedDocumentField>
     */
    private function mergeFields(array $regexFields, array $aiFields): array
    {
        $merged = [];

        foreach ($regexFields as $field) {
            $merged[$field->key] = $field;
        }

        foreach ($aiFields as $field) {
            $current = $merged[$field->key] ?? null;
            $currentHasValue = $current instanceof ExtractedDocumentField && $current->value !== null && $current->value !== '';
            $aiHasValue = $field->value !== null && $field->value !== '';

            if (! $current instanceof ExtractedDocumentField || (! $currentHasValue && $aiHasValue) || ($aiHasValue && $field->confidence > $current->confidence)) {
                $merged[$field->key] = $field;
            }
        }

        return array_values($merged);
    }

    private function auditResult(DocumentAiAnalysis $analysis, DocumentExtractionResult $result): void
    {
        $action = $result->requiresManualReview
            ? 'document_ai_field_extraction_requires_review'
            : 'document_ai_field_extraction_completed';
        $description = $result->requiresManualReview
            ? 'Extração estruturada requer revisão manual.'
            : 'Extração estruturada concluída.';

        $this->audit(AuditEvents::UPDATE, $analysis, $action, $description, [
            'document_type' => $result->documentType->value,
            'status' => $result->status->value,
            'confidence' => $result->confidence,
            'fields_count' => count($result->fields),
            'flags_count' => count($result->flags),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function log(DocumentAiAnalysis $analysis, string $step, string $level, string $message, ?array $context = null): void
    {
        $log = new DocumentAiProcessingLog([
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordFlag(DocumentAiAnalysis $analysis, string $code, string $severity, string $message, array $details): void
    {
        $flag = new DocumentAiFlag([
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'details' => $details,
            'requires_manual_review' => true,
        ]);
        $flag->forceFill(['document_ai_analysis_id' => $analysis->id]);
        $flag->save();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(string $event, DocumentAiAnalysis $analysis, string $action, string $description, array $metadata): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $analysis,
            module: 'documents',
            action: $action,
            description: $description,
            metadata: [
                'document_ai_analysis_id' => $analysis->id,
                'document_submission_id' => $analysis->document_submission_id,
                ...$metadata,
            ],
        );
    }

    private function dispatchCandidateValidation(DocumentAiAnalysis $analysis): void
    {
        if (! (bool) config('document-ai-validation.enabled', true)) {
            return;
        }

        $analysis->loadMissing('documentSubmission.application', 'documentSubmission.adhesionRegistration');
        $submission = $analysis->documentSubmission;

        if (! $submission instanceof DocumentSubmission) {
            return;
        }

        $application = $submission->application;

        if (! $application instanceof Application) {
            $application = $submission->applications()
                ->latest('applications.created_at')
                ->first();
        }

        if (! $application instanceof Application && $submission->adhesionRegistration !== null) {
            $application = $submission->adhesionRegistration
                ->applications()
                ->latest()
                ->first();
        }

        if (! $application instanceof Application) {
            $this->log($analysis, 'candidate_validation_skipped', 'info', 'Validação IA contra candidatura ignorada: candidatura não associada.');

            return;
        }

        ValidateDocumentAiAgainstApplicationJob::dispatch((int) $analysis->id, (int) $application->id)
            ->onQueue((string) config('document-ai-validation.queue', 'default'));
    }

    private function failureCode(Throwable $exception): string
    {
        $class = str_replace('\\', '_', $exception::class);

        return strtolower($class);
    }
}
