<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use App\Events\DocumentAnalysisCompleted;
use App\Events\DocumentAnalysisFailed;
use App\Events\DocumentAnalysisStarted;
use App\Jobs\ProcessDocumentAiJob;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use App\Models\DocumentAiFlag;
use App\Models\DocumentAiProcessingLog;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DocumentAiPipeline
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DocumentClassificationPipeline $classificationPipeline,
    ) {}

    public function createPendingForDocument(Model $document, ?User $actor = null): DocumentAiAnalysis
    {
        if (! $document instanceof DocumentSubmission) {
            return $this->createGenericPendingAnalysis($document, $actor);
        }

        $document->loadMissing('currentVersion');
        $version = $document->currentVersion instanceof DocumentVersion
            ? $document->currentVersion
            : null;

        $existingQuery = DocumentAiAnalysis::query()
            ->where('document_submission_id', $document->id)
            ->whereIn('status', [
                DocumentAiStatus::Pending->value,
                DocumentAiStatus::Processing->value,
            ]);

        if ($version instanceof DocumentVersion) {
            $existingQuery->where('document_version_id', $version->id);
        }

        $existing = $existingQuery->first();

        if ($existing instanceof DocumentAiAnalysis) {
            return $existing;
        }

        $analysis = new DocumentAiAnalysis;
        $analysis->forceFill([
            'document_submission_id' => $document->id,
            'document_version_id' => $version?->id,
            'documentable_type' => $document->getMorphClass(),
            'documentable_id' => $document->getKey(),
            'status' => DocumentAiStatus::Pending,
            'engine' => 'local_document_ai_pipeline',
            'model' => $this->ollamaModel(),
            'source_disk' => $version instanceof DocumentVersion ? $version->storage_disk : $document->storage_disk,
            'source_path' => $version instanceof DocumentVersion ? $version->storage_path : $document->storage_path,
            'source_mime' => $version instanceof DocumentVersion ? $version->mime_type : $document->mime_type,
            'source_size_bytes' => $version instanceof DocumentVersion ? $version->file_size : $document->file_size,
            'source_sha256' => $version instanceof DocumentVersion ? $version->checksum : $document->checksum,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);
        $analysis->save();

        $this->log($analysis, 'queued', 'info', 'Análise documental pendente criada.', [
            'document_submission_id' => $document->id,
            'document_version_id' => $version?->id,
            'source_mime' => $analysis->source_mime,
            'source_size_bytes' => $analysis->source_size_bytes,
        ]);
        $this->audit(AuditEvents::CREATE, $analysis, 'document_ai_pending_created', 'Análise documental por IA criada em estado pendente.', [
            'document_submission_id' => $document->id,
            'document_version_id' => $version?->id,
            'status' => DocumentAiStatus::Pending->value,
            'actor_id' => $actor?->id,
        ]);

        return $analysis->fresh(['documentSubmission', 'documentVersion']) ?? $analysis;
    }

    public function dispatch(DocumentAiAnalysis $analysis): void
    {
        if (! (bool) config('document-ai.enabled', true)) {
            $this->log($analysis, 'dispatch_skipped', 'info', 'Document Intelligence desativado por configuração.', [
                'status' => $analysis->status->value,
            ]);

            return;
        }

        ProcessDocumentAiJob::dispatch($analysis->id)->onQueue((string) config('document-ai.queue', 'default'));
    }

    public function process(DocumentAiAnalysis $analysis): DocumentAiAnalysis
    {
        try {
            $analysis = $this->markProcessing($analysis);

            if (! $this->sourceExists($analysis)) {
                return $this->markFailedWithCode($analysis, 'source_missing', 'Ficheiro privado não encontrado para análise.');
            }

            $this->log($analysis, 'source_checked', 'info', 'Fonte documental privada confirmada.', [
                'source_mime' => $analysis->source_mime,
                'source_size_bytes' => $analysis->source_size_bytes,
                'source_sha256_present' => $analysis->source_sha256 !== null,
            ]);

            return $this->classificationPipeline->process($analysis);
        } catch (Throwable $exception) {
            return $this->markFailed($analysis, $exception);
        }
    }

    public function markFailed(DocumentAiAnalysis $analysis, Throwable $exception): DocumentAiAnalysis
    {
        return $this->markFailedWithCode($analysis, $this->failureCode($exception), 'Falha técnica controlada na pipeline de análise documental.');
    }

    /**
     * @param  array{key: string, label?: string|null, value?: string|null, normalized_value?: string|null, value_type?: string|null, confidence?: numeric-string|float|int|null, page?: int|null, bbox?: array<string, mixed>|null, metadata?: array<string, mixed>|null}  $data
     */
    public function recordField(DocumentAiAnalysis $analysis, array $data): DocumentAiField
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
    public function recordFlag(DocumentAiAnalysis $analysis, array $data): DocumentAiFlag
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

        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_flag_created', 'Flag técnica de análise documental criada.', [
            'flag_code' => $flag->code,
            'severity' => $flag->severity,
            'requires_manual_review' => $flag->requires_manual_review,
        ]);

        return $flag;
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function log(
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
            'context' => $this->minimizedContext($context),
            'duration_ms' => $durationMs,
        ]);
        $log->forceFill([
            'document_ai_analysis_id' => $analysis->id,
            'created_at' => now(),
        ]);
        $log->save();

        return $log;
    }

    private function createGenericPendingAnalysis(Model $document, ?User $actor = null): DocumentAiAnalysis
    {
        $analysis = new DocumentAiAnalysis;
        $analysis->forceFill([
            'documentable_type' => $document->getMorphClass(),
            'documentable_id' => $document->getKey(),
            'status' => DocumentAiStatus::Pending,
            'engine' => 'local_document_ai_pipeline',
            'model' => $this->ollamaModel(),
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]);
        $analysis->save();

        $this->log($analysis, 'queued', 'info', 'Análise documental genérica pendente criada.');

        return $analysis;
    }

    private function markProcessing(DocumentAiAnalysis $analysis): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'status' => DocumentAiStatus::Processing,
            'started_at' => $analysis->started_at ?? now(),
            'failed_at' => null,
            'failure_reason' => null,
        ])->save();

        $this->log($analysis, 'started', 'info', 'Processamento de análise documental iniciado.', [
            'previous_status' => DocumentAiStatus::Pending->value,
            'current_status' => DocumentAiStatus::Processing->value,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_processing_started', 'Processamento de análise documental iniciado.', [
            'status' => DocumentAiStatus::Processing->value,
        ]);
        event(new DocumentAnalysisStarted($analysis->id));

        return $analysis;
    }

    public function markManualReview(DocumentAiAnalysis $analysis, string $reason): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'status' => DocumentAiStatus::ManualReview,
            'manual_review_at' => now(),
            'completed_at' => now(),
            'failure_reason' => $reason,
            'summary' => 'Análise encaminhada para revisão manual.',
            'confidence' => '0.00',
        ])->save();

        $this->log($analysis, 'manual_review', 'warning', 'Análise documental encaminhada para revisão manual.', [
            'flags_count' => $analysis->flags()->count(),
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_manual_review', 'Análise documental encaminhada para revisão manual.', [
            'status' => DocumentAiStatus::ManualReview->value,
        ]);
        event(new DocumentAnalysisCompleted($analysis->id, DocumentAiStatus::ManualReview));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    private function markFailedWithCode(DocumentAiAnalysis $analysis, string $code, string $reason): DocumentAiAnalysis
    {
        $analysis->forceFill([
            'status' => DocumentAiStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        $this->log($analysis, 'failed', 'error', 'Falha controlada na análise documental.', [
            'failure_code' => $code,
        ]);
        $this->audit(AuditEvents::UPDATE, $analysis, 'document_ai_failed', 'Análise documental por IA falhou de forma controlada.', [
            'status' => DocumentAiStatus::Failed->value,
            'failure_code' => $code,
        ]);
        event(new DocumentAnalysisFailed($analysis->id, $code));

        return $analysis->fresh(['fields', 'flags', 'processingLogs']) ?? $analysis;
    }

    private function sourceExists(DocumentAiAnalysis $analysis): bool
    {
        if ($analysis->source_disk === null || $analysis->source_path === null) {
            return false;
        }

        return Storage::disk($analysis->source_disk)->exists($analysis->source_path);
    }

    /**
     * @return array<string, array{binary: string, available: bool, required: bool}>
     */
    public function toolAvailability(): array
    {
        if (! (bool) config('document-ai.processing.check_local_tools', true)) {
            return [
                'tesseract' => ['binary' => $this->configString('document-ai.ocr.binary', 'tesseract'), 'available' => true, 'required' => true],
                'pdftotext' => ['binary' => $this->configString('document-ai.pdf.pdftotext_binary', 'pdftotext'), 'available' => true, 'required' => true],
                'pdfimages' => ['binary' => $this->configString('document-ai.pdf.pdfimages_binary', 'pdfimages'), 'available' => true, 'required' => true],
                'pdftoppm' => ['binary' => $this->configString('document-ai.pdf.pdftoppm_binary', 'pdftoppm'), 'available' => true, 'required' => true],
                'magick' => ['binary' => $this->configString('document-ai.image.magick_binary', 'magick'), 'available' => true, 'required' => true],
                'ollama' => ['binary' => 'ollama', 'available' => ! (bool) config('document-ai.ollama.enabled', false), 'required' => (bool) config('document-ai.ollama.enabled', false)],
            ];
        }

        return [
            'tesseract' => ['binary' => $this->configString('document-ai.ocr.binary', 'tesseract'), 'available' => $this->binaryAvailable($this->configString('document-ai.ocr.binary', 'tesseract')), 'required' => true],
            'pdftotext' => ['binary' => $this->configString('document-ai.pdf.pdftotext_binary', 'pdftotext'), 'available' => $this->binaryAvailable($this->configString('document-ai.pdf.pdftotext_binary', 'pdftotext')), 'required' => true],
            'pdfimages' => ['binary' => $this->configString('document-ai.pdf.pdfimages_binary', 'pdfimages'), 'available' => $this->binaryAvailable($this->configString('document-ai.pdf.pdfimages_binary', 'pdfimages')), 'required' => true],
            'pdftoppm' => ['binary' => $this->configString('document-ai.pdf.pdftoppm_binary', 'pdftoppm'), 'available' => $this->binaryAvailable($this->configString('document-ai.pdf.pdftoppm_binary', 'pdftoppm')), 'required' => true],
            'magick' => ['binary' => $this->configString('document-ai.image.magick_binary', 'magick'), 'available' => $this->binaryAvailable($this->configString('document-ai.image.magick_binary', 'magick')), 'required' => true],
            'ollama' => ['binary' => 'ollama', 'available' => ! (bool) config('document-ai.ollama.enabled', false) || $this->binaryAvailable('ollama'), 'required' => (bool) config('document-ai.ollama.enabled', false)],
        ];
    }

    private function binaryAvailable(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        if (str_contains($binary, DIRECTORY_SEPARATOR)) {
            return is_file($binary) && is_executable($binary);
        }

        try {
            $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary));
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, array{binary: string, available: bool, required: bool}>  $tools
     * @return list<string>
     */
    public function missingRequiredTools(array $tools): array
    {
        $missing = [];

        foreach ($tools as $name => $tool) {
            if ($tool['required'] && ! $tool['available']) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, array{binary: string, available: bool, required: bool}>  $tools
     * @param  list<string>  $missingTools
     * @return array<string, mixed>
     */
    public function rawPayload(DocumentAiAnalysis $analysis, array $tools, array $missingTools): array
    {
        return [
            'schema_version' => 'sprint27.infrastructure.v1',
            'engine' => 'local_document_ai_pipeline',
            'model' => $this->ollamaModel(),
            'document' => [
                'document_submission_id' => $analysis->document_submission_id,
                'document_version_id' => $analysis->document_version_id,
                'source_mime' => $analysis->source_mime,
                'source_size_bytes' => $analysis->source_size_bytes,
                'source_sha256_present' => $analysis->source_sha256 !== null,
            ],
            'tools' => $tools,
            'missing_required_tools' => $missingTools,
            'outputs' => [
                'classification' => null,
                'fields' => [],
                'flags' => $missingTools,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $context
     * @return array<string, mixed>|null
     */
    private function minimizedContext(?array $context): ?array
    {
        if ($context === null) {
            return null;
        }

        $allowed = [
            'document_submission_id',
            'document_version_id',
            'source_mime',
            'source_size_bytes',
            'source_sha256_present',
            'previous_status',
            'current_status',
            'status',
            'failure_code',
            'fields_count',
            'flags_count',
            'schema_version',
        ];

        return array_intersect_key($context, array_flip($allowed));
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

    private function ollamaModel(): string
    {
        return $this->configString('document-ai.ollama.model', 'gemma3:4b');
    }

    private function configString(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function failureCode(Throwable $exception): string
    {
        $class = str_replace('\\', '_', $exception::class);

        return strtolower($class);
    }
}
