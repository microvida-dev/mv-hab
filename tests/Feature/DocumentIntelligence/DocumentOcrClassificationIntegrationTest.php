<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiStatus;
use App\Events\DocumentClassificationCompleted;
use App\Events\DocumentOcrCompleted;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Services\DocumentIntelligence\DocumentAiPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentOcrClassificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_extracts_ocr_classifies_document_and_keeps_document_workflow_unchanged(): void
    {
        Event::fake();
        Storage::fake('local');
        config([
            'document-ai.ocr.fallback_to_source_text' => true,
            'document-ai.ollama.enabled' => false,
        ]);
        Storage::disk('local')->put('documents/sprint28/irs.pdf', (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/extraction/irs.txt')));
        $submission = DocumentSubmission::factory()->create([
            'storage_disk' => 'local',
            'storage_path' => 'documents/sprint28/irs.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 200,
            'checksum' => hash('sha256', 'irs-sprint28'),
        ]);
        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_disk' => 'local',
            'storage_path' => 'documents/sprint28/irs.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 200,
            'checksum' => hash('sha256', 'irs-sprint28'),
        ]);
        $submission->forceFill(['current_version_id' => $version->id])->save();
        $analysis = app(DocumentAiPipeline::class)->createPendingForDocument($submission->fresh('currentVersion'));

        $processed = app(DocumentAiPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::Completed, $processed->status);
        $this->assertTrue($processed->ocr_available);
        $this->assertSame(DocumentAiDocumentType::Irs, $processed->detected_document_type);
        $this->assertSame('sprint29.structured_extraction.v1', $processed->raw_ai_json['schema_version']);
        $this->assertArrayHasKey('extraction', $processed->raw_ai_json);
        $this->assertDatabaseHas('document_ai_fields', [
            'document_ai_analysis_id' => $processed->id,
            'key' => 'document_type',
            'normalized_value' => DocumentAiDocumentType::Irs->value,
        ]);
        $this->assertDatabaseHas('document_ai_fields', [
            'document_ai_analysis_id' => $processed->id,
            'key' => 'gross_income',
            'normalized_value' => '18500.75',
        ]);
        $this->assertDatabaseMissing('document_submissions', [
            'id' => $submission->id,
            'status' => 'validated',
        ]);

        Event::assertDispatched(DocumentOcrCompleted::class);
        Event::assertDispatched(DocumentClassificationCompleted::class);
    }

    public function test_missing_source_still_fails_controlled_without_touching_document_submission(): void
    {
        Storage::fake('local');
        config(['document-ai.ocr.fallback_to_source_text' => true]);
        $submission = DocumentSubmission::factory()->create([
            'storage_disk' => 'local',
            'storage_path' => 'documents/sprint28/missing.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $analysis = DocumentAiAnalysis::factory()->create([
            'document_submission_id' => $submission->id,
            'source_disk' => 'local',
            'source_path' => 'documents/sprint28/missing.pdf',
            'source_mime' => 'application/pdf',
        ]);

        $processed = app(DocumentAiPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::Failed, $processed->status);
        $this->assertDatabaseHas('document_submissions', [
            'id' => $submission->id,
            'status' => $submission->status->value,
        ]);
    }
}
