<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiStatus;
use App\Events\DocumentFieldExtractionCompleted;
use App\Models\DocumentAiAnalysis;
use App\Services\DocumentIntelligence\DocumentFieldExtractionPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DocumentFieldExtractionPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_persists_structured_extraction_without_touching_document_submission_status(): void
    {
        Event::fake();
        config(['document-ai-extraction.ollama.enabled' => false]);
        $analysis = $this->classifiedAnalysis(
            DocumentAiDocumentType::Irs,
            (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/extraction/irs.txt')),
        );
        $submissionStatus = $analysis->documentSubmission?->status->value;

        $processed = app(DocumentFieldExtractionPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::Completed, $processed->status);
        $this->assertSame(DocumentAiExtractionStatus::Completed, $processed->extraction_status);
        $this->assertIsArray($processed->extraction_json);
        $this->assertSame('sprint29.structured_extraction.v1', $processed->raw_ai_json['schema_version']);
        $this->assertDatabaseHas('document_ai_fields', [
            'document_ai_analysis_id' => $processed->id,
            'document_type' => DocumentAiDocumentType::Irs->value,
            'key' => 'gross_income',
            'normalized_value' => '18500.75',
            'requires_review' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $processed->getMorphClass(),
            'auditable_id' => $processed->id,
            'action' => 'document_ai_field_extraction_completed',
        ]);
        $this->assertSame($submissionStatus, $processed->documentSubmission?->fresh()?->status->value);

        Event::assertDispatched(DocumentFieldExtractionCompleted::class);
    }

    public function test_pipeline_requires_manual_review_when_required_fields_are_missing(): void
    {
        $analysis = $this->classifiedAnalysis(DocumentAiDocumentType::CartaoCidadao, 'Cartao de Cidadao Nome: Documento Parcial');

        $processed = app(DocumentFieldExtractionPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::ManualReview, $processed->status);
        $this->assertTrue($processed->extraction_requires_manual_review);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $processed->id,
            'code' => 'missing_required_field',
            'requires_manual_review' => true,
        ]);
    }

    public function test_pipeline_marks_unsupported_document_types_without_breaking_analysis(): void
    {
        $analysis = $this->classifiedAnalysis(DocumentAiDocumentType::Iban, 'IBAN: PT50000000000000000000000');

        $processed = app(DocumentFieldExtractionPipeline::class)->process($analysis);

        $this->assertSame(DocumentAiStatus::ManualReview, $processed->status);
        $this->assertSame(DocumentAiExtractionStatus::UnsupportedDocumentType, $processed->extraction_status);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $processed->id,
            'code' => 'unsupported_document_type',
        ]);
    }

    private function classifiedAnalysis(DocumentAiDocumentType $documentType, string $ocrText): DocumentAiAnalysis
    {
        return DocumentAiAnalysis::factory()->completed()->create([
            'status' => DocumentAiStatus::Completed,
            'ocr_status' => DocumentAiOcrStatus::Completed,
            'ocr_available' => true,
            'ocr_text' => $ocrText,
            'raw_text' => $ocrText,
            'classification_status' => DocumentAiClassificationStatus::Completed,
            'detected_document_type' => $documentType,
            'detected_document_label' => $documentType->label(),
            'classification_confidence' => '0.96',
            'classification_source' => 'ocr+keywords+layout',
            'classification_requires_manual_review' => false,
        ]);
    }
}
