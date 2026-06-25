<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionSource;
use App\Enums\DocumentAiRiskFlagCode;
use App\Models\DocumentAiField;
use App\Services\DocumentIntelligence\DocumentRiskFlagDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentRiskFlagServiceTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_detector_flags_expired_document_without_deciding_validity(): void
    {
        [,, $analysis] = $this->createAssistantAnalysis();
        DocumentAiField::factory()->create([
            'document_ai_analysis_id' => $analysis->id,
            'key' => 'valid_until',
            'label' => 'Validade',
            'value' => now()->subDay()->toDateString(),
            'normalized_value' => now()->subDay()->toDateString(),
            'value_type' => DocumentAiExtractedFieldType::Date->value,
            'source' => DocumentAiExtractionSource::Regex->value,
            'confidence' => '0.95',
        ]);

        $flags = app(DocumentRiskFlagDetector::class)->detect($analysis->fresh(['fields', 'validations']) ?? $analysis);

        $this->assertTrue(collect($flags)->contains(
            fn ($flag): bool => $flag->code === DocumentAiRiskFlagCode::DocumentExpired
        ));
    }
}
