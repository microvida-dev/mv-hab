<?php

namespace Database\Factories;

use App\Enums\DocumentAiStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiAnalysis>
 */
class DocumentAiAnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_submission_id' => DocumentSubmission::factory(),
            'document_version_id' => null,
            'status' => DocumentAiStatus::Pending->value,
            'engine' => 'local_document_ai_pipeline',
            'model' => 'gemma3:4b',
            'source_disk' => 'local',
            'source_path' => 'documents/test/documento-teste.pdf',
            'source_mime' => 'application/pdf',
            'source_size_bytes' => 1024,
            'source_sha256' => hash('sha256', fake()->uuid()),
            'raw_text' => null,
            'raw_ai_json' => null,
            'summary' => null,
            'confidence' => null,
            'ocr_status' => null,
            'ocr_available' => false,
            'ocr_engine' => null,
            'ocr_language' => null,
            'ocr_text' => null,
            'ocr_quality_score' => null,
            'ocr_pages_count' => null,
            'ocr_processed_at' => null,
            'classification_status' => null,
            'detected_document_type' => null,
            'detected_document_label' => null,
            'classification_confidence' => null,
            'classification_source' => null,
            'classification_model' => null,
            'classification_prompt_version' => null,
            'classification_signals' => null,
            'classification_requires_manual_review' => false,
            'classified_at' => null,
            'extraction_status' => null,
            'extraction_schema_version' => null,
            'extraction_json' => null,
            'extraction_confidence' => null,
            'extraction_model' => null,
            'extraction_prompt_version' => null,
            'extraction_started_at' => null,
            'extraction_completed_at' => null,
            'extraction_failed_at' => null,
            'extraction_requires_manual_review' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function forVersion(DocumentVersion $version): static
    {
        return $this->state(fn () => [
            'document_submission_id' => $version->document_submission_id,
            'document_version_id' => $version->id,
            'source_disk' => $version->storage_disk,
            'source_path' => $version->storage_path,
            'source_mime' => $version->mime_type,
            'source_size_bytes' => $version->file_size,
            'source_sha256' => $version->checksum,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => DocumentAiStatus::Processing->value,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => DocumentAiStatus::Completed->value,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'raw_ai_json' => ['schema_version' => 'sprint28.ocr_classification.v1'],
        ]);
    }
}
