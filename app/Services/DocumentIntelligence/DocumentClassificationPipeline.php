<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\OcrResult;
use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiStatus;
use App\Events\DocumentAnalysisCompleted;
use App\Events\DocumentClassificationCompleted;
use App\Events\DocumentClassificationFailed;
use App\Events\DocumentClassificationRequiresReview;
use App\Events\DocumentClassificationStarted;
use App\Events\DocumentOcrCompleted;
use App\Events\DocumentOcrFailed;
use App\Events\DocumentOcrStarted;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;

class DocumentClassificationPipeline
{
    public function __construct(
        private readonly DocumentTextExtractor $textExtractor,
        private readonly DocumentKeywordClassifier $keywordClassifier,
        private readonly DocumentLayoutSignalExtractor $layoutSignalExtractor,
        private readonly LocalAiDocumentClassifier $localAiClassifier,
        private readonly DocumentClassificationScorer $scorer,
        private readonly DocumentFieldExtractionPipeline $fieldExtractionPipeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function process(DocumentAiAnalysis $analysis): DocumentAiAnalysis
    {
        event(new DocumentOcrStarted($analysis->id));
        $this->log($analysis, 'ocr_started', 'info', 'OCR iniciado.', [
            'source_mime' => $analysis->source_mime,
            'source_size_bytes' => $analysis->source_size_bytes,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_ocr_started', 'OCR documental iniciado.', []);

        $ocr = $this->textExtractor->extract($analysis);
        $this->persistOcr($analysis, $ocr);

        if (! $ocr->available || $ocr->text === null || $ocr->text === '') {
            $this->recordFlag($analysis, [
                'code' => $ocr->failureCode ?? 'ocr_unavailable',
                'severity' => 'medium',
                'message' => 'OCR indisponível; documento requer revisão manual.',
                'details' => ['method' => $ocr->method, 'status' => $ocr->status->value],
                'requires_manual_review' => true,
            ]);

            event(new DocumentOcrFailed($analysis->id, $ocr->failureCode ?? 'ocr_unavailable'));
            event(new DocumentClassificationFailed($analysis->id, 'ocr_unavailable'));

            return $this->manualReview($analysis, $ocr, 'OCR indisponível para classificação automática.');
        }

        event(new DocumentOcrCompleted($analysis->id, $ocr->available));
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_ocr_completed', 'OCR documental concluído.', [
            'ocr_status' => $ocr->status->value,
            'ocr_available' => $ocr->available,
            'ocr_method' => $ocr->method,
            'ocr_quality_score' => $ocr->qualityScore,
        ]);

        event(new DocumentClassificationStarted($analysis->id));
        $this->log($analysis, 'classification_started', 'info', 'Classificação automática iniciada.', [
            'schema_version' => 'sprint28.ocr_classification.v1',
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_classification_started', 'Classificação documental iniciada.', []);

        $keyword = $this->keywordClassifier->classify($ocr->text);
        $layout = $this->layoutSignalExtractor->extract($ocr->text);
        $ai = $this->localAiClassifier->classify($ocr->text);
        $classification = $this->scorer->score($keyword, $layout, $ai);

        $analysis->forceFill([
            'status' => $classification->requiresManualReview ? DocumentAiStatus::ManualReview : DocumentAiStatus::Completed,
            'classification_status' => $classification->status,
            'detected_document_type' => $classification->documentType,
            'detected_document_label' => $classification->label,
            'classification_confidence' => $classification->confidence,
            'classification_source' => $classification->source,
            'classification_model' => (string) config('document-ai.ollama.model', 'gemma3:4b'),
            'classification_prompt_version' => (string) config('document-ai-classification.prompt_version', 'sprint28.classification.v1'),
            'classification_signals' => $classification->signals,
            'classification_requires_manual_review' => $classification->requiresManualReview,
            'classified_at' => now(),
            'completed_at' => now(),
            'manual_review_at' => $classification->requiresManualReview ? now() : null,
            'failed_at' => null,
            'failure_reason' => $classification->requiresManualReview ? 'Classificação automática requer revisão manual.' : null,
            'summary' => $this->summary($classification->label, $classification->confidence, $classification->requiresManualReview),
            'confidence' => $classification->confidence,
            'raw_ai_json' => $this->rawPayload($analysis, $ocr, $classification->raw),
        ])->save();

        $this->recordField($analysis, [
            'key' => 'document_type',
            'label' => 'Tipo documental classificado',
            'value' => $classification->label,
            'normalized_value' => $classification->documentType->value,
            'value_type' => 'enum',
            'confidence' => $classification->confidence,
            'metadata' => ['source' => $classification->source],
        ]);

        if ($ai->failureCode !== null && $ai->failureCode !== 'ollama_disabled') {
            $this->recordFlag($analysis, [
                'code' => $ai->failureCode,
                'severity' => 'low',
                'message' => 'IA local indisponível ou resposta inválida; classificação manteve fallback determinístico.',
                'details' => ['source' => $ai->source],
                'requires_manual_review' => true,
            ]);
        }

        if ($classification->requiresManualReview) {
            $this->recordFlag($analysis, [
                'code' => $classification->status === DocumentAiClassificationStatus::LowConfidence ? 'classification_low_confidence' : 'classification_manual_review',
                'severity' => 'medium',
                'message' => 'Classificação automática requer confirmação manual.',
                'details' => ['confidence' => $classification->confidence, 'type' => $classification->documentType->value],
                'requires_manual_review' => true,
            ]);
            $this->log($analysis, 'classification_manual_review', 'warning', 'Classificação encaminhada para revisão manual.', [
                'status' => DocumentAiStatus::ManualReview->value,
            ]);
            $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_classification_requires_review', 'Classificação documental requer revisão manual.', [
                'document_type' => $classification->documentType->value,
                'confidence' => $classification->confidence,
            ]);
            event(new DocumentClassificationRequiresReview($analysis->id, $classification->documentType, $classification->confidence));
        } else {
            $this->log($analysis, 'classification_completed', 'info', 'Classificação automática concluída.', [
                'status' => DocumentAiStatus::Completed->value,
            ]);
            $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_classification_completed', 'Classificação documental concluída.', [
                'document_type' => $classification->documentType->value,
                'confidence' => $classification->confidence,
            ]);
            event(new DocumentClassificationCompleted($analysis->id, $classification->documentType, $classification->confidence));
        }

        $analysis = $this->fieldExtractionPipeline->process($analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis);
        event(new DocumentAnalysisCompleted($analysis->id, $analysis->status));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    private function persistOcr(DocumentAiAnalysis $analysis, OcrResult $ocr): void
    {
        $analysis->forceFill([
            'ocr_status' => $ocr->status,
            'ocr_available' => $ocr->available,
            'ocr_engine' => $ocr->method,
            'ocr_language' => (string) config('document-ai.ocr.language', 'por+eng'),
            'ocr_text' => $ocr->text,
            'raw_text' => $ocr->text,
            'ocr_quality_score' => $ocr->qualityScore,
            'ocr_pages_count' => $ocr->pagesCount,
            'ocr_processed_at' => now(),
        ])->save();

        $this->log($analysis, $ocr->available ? 'ocr_completed' : 'ocr_unavailable', $ocr->available ? 'info' : 'warning', $ocr->available ? 'OCR concluído.' : 'OCR indisponível.', [
            'schema_version' => 'sprint28.ocr_classification.v1',
        ], $ocr->durationMs);
    }

    private function manualReview(DocumentAiAnalysis $analysis, OcrResult $ocr, string $reason): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'status' => DocumentAiStatus::ManualReview,
            'classification_status' => DocumentAiClassificationStatus::ManualReview,
            'detected_document_type' => null,
            'detected_document_label' => null,
            'classification_confidence' => '0.00',
            'classification_source' => 'ocr',
            'classification_model' => (string) config('document-ai.ollama.model', 'gemma3:4b'),
            'classification_prompt_version' => (string) config('document-ai-classification.prompt_version', 'sprint28.classification.v1'),
            'classification_signals' => $ocr->signals,
            'classification_requires_manual_review' => true,
            'classified_at' => now(),
            'completed_at' => now(),
            'manual_review_at' => now(),
            'failure_reason' => $reason,
            'summary' => 'Classificação automática indisponível. Revisão manual necessária.',
            'confidence' => '0.00',
            'raw_ai_json' => $this->rawPayload($analysis, $ocr, ['classification' => null]),
        ])->save();
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_classification_manual_review', 'Classificação documental encaminhada para revisão manual.', [
            'reason' => 'ocr_unavailable',
        ]);
        event(new DocumentAnalysisCompleted($analysis->id, DocumentAiStatus::ManualReview));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    /**
     * @param  array{key: string, label?: string|null, value?: string|null, normalized_value?: string|null, value_type?: string|null, confidence?: numeric-string|float|int|null, page?: int|null, bbox?: array<string, mixed>|null, metadata?: array<string, mixed>|null}  $data
     */
    private function recordField(DocumentAiAnalysis $analysis, array $data): DocumentAiField
    {
        $field = new DocumentAiField([
            'key' => $data['key'],
            'label' => $data['label'] ?? null,
            'value' => $data['value'] ?? null,
            'normalized_value' => $data['normalized_value'] ?? null,
            'value_type' => $data['value_type'] ?? null,
            'confidence' => $data['confidence'] ?? null,
            'page' => $data['page'] ?? null,
            'bbox' => $data['bbox'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
        $field->forceFill(['document_ai_analysis_id' => $analysis->id]);
        $field->save();

        return $field;
    }

    /**
     * @param  array{code: string, severity: string, message: string, details?: array<string, mixed>|null, requires_manual_review?: bool}  $data
     */
    private function recordFlag(DocumentAiAnalysis $analysis, array $data): DocumentAiFlag
    {
        $flag = new DocumentAiFlag([
            'code' => $data['code'],
            'severity' => $data['severity'],
            'message' => $data['message'],
            'details' => $data['details'] ?? null,
            'requires_manual_review' => $data['requires_manual_review'] ?? false,
        ]);
        $flag->forceFill(['document_ai_analysis_id' => $analysis->id]);
        $flag->save();

        return $flag;
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function log(
        DocumentAiAnalysis $analysis,
        string $step,
        string $level,
        string $message,
        ?array $context = null,
        ?int $durationMs = null,
    ): DocumentAiProcessingLog {
        $log = new DocumentAiProcessingLog([
            'step' => $step,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'duration_ms' => $durationMs,
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();

        return $log;
    }

    /**
     * @param  array<string, mixed>  $classificationRaw
     * @return array<string, mixed>
     */
    private function rawPayload(DocumentAiAnalysis $analysis, OcrResult $ocr, array $classificationRaw): array
    {
        return [
            'schema_version' => 'sprint28.ocr_classification.v1',
            'engine' => 'local_document_ai_pipeline',
            'model' => (string) config('document-ai.ollama.model', 'gemma3:4b'),
            'document' => [
                'document_submission_id' => $analysis->document_submission_id,
                'document_version_id' => $analysis->document_version_id,
                'source_mime' => $analysis->source_mime,
                'source_size_bytes' => $analysis->source_size_bytes,
                'source_sha256_present' => $analysis->source_sha256 !== null,
            ],
            'ocr' => [
                'status' => $ocr->status->value,
                'available' => $ocr->available,
                'method' => $ocr->method,
                'quality_score' => $ocr->qualityScore,
                'pages_count' => $ocr->pagesCount,
                'signals' => $ocr->signals,
                'failure_code' => $ocr->failureCode,
            ],
            'classification' => $classificationRaw,
            'generated_at' => now()->toIso8601String(),
        ];
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

    private function summary(string $label, float $confidence, bool $manualReview): string
    {
        $suffix = $manualReview ? ' Requer confirmação manual.' : '';

        return 'Documento classificado como '.$label.' com confiança '.number_format($confidence * 100, 0).'%.'.$suffix;
    }
}
