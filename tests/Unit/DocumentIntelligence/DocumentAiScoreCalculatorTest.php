<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiScoreLabel;
use App\Services\DocumentIntelligence\DocumentAiScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentAiScoreCalculatorTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_it_calculates_high_confidence_score_without_changing_application(): void
    {
        [$application,, $analysis] = $this->createAssistantAnalysis();
        $originalStatus = $application->status;

        $result = app(DocumentAiScoreCalculator::class)->calculate($analysis, []);

        $this->assertGreaterThanOrEqual(75, $result->score);
        $this->assertContains($result->label, [
            DocumentAiScoreLabel::MuitoConfiavel,
            DocumentAiScoreLabel::ConfiavelComAtencao,
        ]);
        $this->assertSame($originalStatus, $application->fresh()->status);
    }

    public function test_configured_flags_reduce_score_and_require_review(): void
    {
        [,, $analysis] = $this->createAssistantAnalysis();

        $result = app(DocumentAiScoreCalculator::class)->calculate($analysis, [
            new DocumentAiRiskFlag(
                code: DocumentAiRiskFlagCode::NifMismatch,
                severity: DocumentAiRiskSeverity::Critical,
                scoreImpact: 45,
                message: 'Divergência controlada de teste.',
                detectedBy: 'test',
                confidence: 0.93,
            ),
        ]);

        $this->assertLessThan(75, $result->score);
        $this->assertTrue($result->requiresManualReview);
        $this->assertSame(45, $result->components['penalty']);
    }
}
