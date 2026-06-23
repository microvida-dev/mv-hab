<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Services\DocumentIntelligence\DocumentQualityAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentQualityAnalyzerTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_it_detects_insufficient_ocr_without_reading_private_file_path(): void
    {
        [,, $analysis] = $this->createAssistantAnalysis([
            'ocr_text' => 'curto',
            'ocr_quality_score' => '0.30',
        ]);

        $flags = app(DocumentQualityAnalyzer::class)->analyze($analysis);

        $this->assertTrue(collect($flags)->contains(
            fn ($flag): bool => $flag->code === DocumentAiRiskFlagCode::InsufficientOcr
        ));
        $this->assertFalse(collect($flags)->contains(
            fn ($flag): bool => str_contains($flag->message, (string) $analysis->source_path)
        ));
    }
}
