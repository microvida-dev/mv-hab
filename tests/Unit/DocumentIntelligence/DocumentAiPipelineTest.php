<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiStatus;
use App\Events\DocumentAnalysisCompleted;
use App\Events\DocumentAnalysisFailed;
use App\Events\DocumentAnalysisStarted;
use App\Jobs\ProcessDocumentAiJob;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentAiPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentAiPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_analysis_and_dispatches_job_with_analysis_id_only(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$submission] = $this->documentSubmissionWithPrivateFile();
        $actor = User::factory()->create();

        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission, $actor);
        app(DocumentAiPipeline::class)->dispatch($analysis);

        $this->assertSame(DocumentAiStatus::Pending, $analysis->status);
        $this->assertSame($submission->id, $analysis->document_submission_id);
        $this->assertSame($submission->current_version_id, $analysis->document_version_id);
        $this->assertDatabaseHas('document_ai_processing_logs', [
            'document_ai_analysis_id' => $analysis->id,
            'step' => 'queued',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $analysis->getMorphClass(),
            'auditable_id' => $analysis->id,
            'module' => 'documents',
            'action' => 'document_ai_pending_created',
        ]);

        Queue::assertPushed(ProcessDocumentAiJob::class, fn (ProcessDocumentAiJob $job): bool => $job->documentAiAnalysisId === $analysis->id);
    }

    public function test_it_processes_pending_analysis_to_completed_with_events_audit_and_raw_json(): void
    {
        Event::fake();
        Storage::fake('local');
        config([
            'document-ai.processing.check_local_tools' => false,
            'document-ai.ocr.fallback_to_source_text' => true,
            'document-ai.ollama.enabled' => false,
        ]);
        [$submission] = $this->documentSubmissionWithPrivateFile();
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission);

        $processed = app(DocumentAiPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::Completed, $processed->status);
        $this->assertIsArray($processed->raw_ai_json);
        $this->assertSame('sprint29.structured_extraction.v1', $processed->raw_ai_json['schema_version']);
        $this->assertArrayHasKey('extraction', $processed->raw_ai_json);
        $this->assertDatabaseHas('document_ai_processing_logs', [
            'document_ai_analysis_id' => $processed->id,
            'step' => 'classification_completed',
        ]);
        $this->assertDatabaseHas('document_ai_processing_logs', [
            'document_ai_analysis_id' => $processed->id,
            'step' => 'field_extraction_completed',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $processed->getMorphClass(),
            'auditable_id' => $processed->id,
            'action' => 'document_ai_classification_completed',
        ]);

        Event::assertDispatched(DocumentAnalysisStarted::class, fn (DocumentAnalysisStarted $event): bool => $event->documentAiAnalysisId === $processed->id);
        Event::assertDispatched(DocumentAnalysisCompleted::class, function (DocumentAnalysisCompleted $event) use ($processed): bool {
            return $event->documentAiAnalysisId === $processed->id
                && $event->status === DocumentAiStatus::Completed
                && ! property_exists($event, 'raw_ai_json')
                && ! property_exists($event, 'raw_text');
        });
    }

    public function test_missing_local_tools_send_analysis_to_manual_review_without_breaking(): void
    {
        Event::fake();
        Storage::fake('local');
        config([
            'document-ai.processing.check_local_tools' => true,
            'document-ai.ocr.binary' => 'missing-tesseract-s27',
            'document-ai.pdf.pdftotext_binary' => 'missing-pdftotext-s27',
            'document-ai.pdf.pdfimages_binary' => 'missing-pdfimages-s27',
            'document-ai.pdf.pdftoppm_binary' => 'missing-pdftoppm-s27',
            'document-ai.image.magick_binary' => 'missing-magick-s27',
            'document-ai.ollama.enabled' => false,
        ]);
        [$submission] = $this->documentSubmissionWithPrivateFile();
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission);

        $processed = app(DocumentAiPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::ManualReview, $processed->status);
        $this->assertGreaterThanOrEqual(1, $processed->flags()->count());
        $this->assertTrue($processed->ocr_available === false);

        Event::assertDispatched(DocumentAnalysisCompleted::class, fn (DocumentAnalysisCompleted $event): bool => $event->status === DocumentAiStatus::ManualReview);
    }

    public function test_missing_private_source_marks_analysis_as_failed(): void
    {
        Event::fake();
        Storage::fake('local');
        config(['document-ai.processing.check_local_tools' => false]);
        [$submission] = $this->documentSubmissionWithPrivateFile(putFile: false);
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission);

        $processed = app(DocumentAiPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::Failed, $processed->status);
        $this->assertSame('Ficheiro privado não encontrado para análise.', $processed->fresh()->failure_reason);

        Event::assertDispatched(DocumentAnalysisFailed::class, fn (DocumentAnalysisFailed $event): bool => $event->documentAiAnalysisId === $processed->id && $event->failureCode === 'source_missing');
    }

    public function test_it_records_structured_fields_flags_and_minimized_logs(): void
    {
        Storage::fake('local');
        [$submission] = $this->documentSubmissionWithPrivateFile();
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission);
        $pipeline = app(DocumentAiPipeline::class);

        $field = $pipeline->recordField($analysis, [
            'key' => 'document_type_hint',
            'label' => 'Tipo documental indicado',
            'value' => 'Documento fictício',
            'normalized_value' => 'documento_ficticio',
            'value_type' => 'string',
            'confidence' => '0.50',
            'metadata' => ['source' => 'unit_test'],
        ]);
        $flag = $pipeline->recordFlag($analysis, [
            'code' => 'ocr_pending',
            'severity' => 'info',
            'message' => 'OCR ainda não executado nesta sprint.',
            'details' => ['phase' => 'sprint_27'],
            'requires_manual_review' => false,
        ]);
        $log = $pipeline->log($analysis, 'metadata_checked', 'info', 'Metadados verificados.', [
            'source_mime' => 'application/pdf',
            'unsafe_name' => 'Pessoa Exemplo',
        ]);

        $this->assertSame($analysis->id, $field->document_ai_analysis_id);
        $this->assertSame($analysis->id, $flag->document_ai_analysis_id);
        $this->assertArrayNotHasKey('unsafe_name', $log->context ?? []);
    }

    public function test_job_loads_analysis_and_delegates_processing_to_pipeline(): void
    {
        Storage::fake('local');
        [$submission] = $this->documentSubmissionWithPrivateFile();
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission);
        $pipeline = Mockery::mock(DocumentAiPipeline::class);
        $pipeline->shouldReceive('process')
            ->once()
            ->with(Mockery::on(fn (DocumentAiAnalysis $argument): bool => $argument->id === $analysis->id));

        (new ProcessDocumentAiJob($analysis->id))->handle($pipeline);
    }

    /**
     * @return array{0: DocumentSubmission, 1: DocumentVersion}
     */
    private function documentSubmissionWithPrivateFile(bool $putFile = true): array
    {
        $submission = DocumentSubmission::factory()->create([
            'storage_disk' => 'local',
            'storage_path' => 'documents/sprint27/documento-teste.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'checksum' => hash('sha256', 'sprint27-documento-ficticio'),
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_disk' => 'local',
            'storage_path' => 'documents/sprint27/documento-teste.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'checksum' => hash('sha256', 'sprint27-documento-ficticio'),
        ]);
        $submission->forceFill([
            'current_version_id' => $version->id,
            'storage_disk' => $version->storage_disk,
            'storage_path' => $version->storage_path,
            'mime_type' => $version->mime_type,
            'file_size' => $version->file_size,
            'checksum' => $version->checksum,
        ])->save();

        if ($putFile) {
            Storage::disk('local')->put($version->storage_path, (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/extraction/irs.txt')));
        }

        return [$submission->fresh('currentVersion'), $version];
    }
}
