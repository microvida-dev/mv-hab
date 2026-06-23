<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Events\DocumentAiRiskFlagDetected;
use App\Events\DocumentAiScoreCalculated;
use App\Events\DocumentAiScoreCalculationStarted;
use App\Events\DocumentAiSuggestionGenerated;
use App\Services\DocumentIntelligence\DocumentAiAssistantPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\CreatesDocumentAiAssistantFixtures;
use Tests\TestCase;

class DocumentAiAssistantPipelineTest extends TestCase
{
    use CreatesDocumentAiAssistantFixtures;
    use RefreshDatabase;

    public function test_pipeline_persists_score_flags_suggestions_events_and_audit_without_state_changes(): void
    {
        Event::fake([
            DocumentAiScoreCalculationStarted::class,
            DocumentAiScoreCalculated::class,
            DocumentAiRiskFlagDetected::class,
            DocumentAiSuggestionGenerated::class,
        ]);

        [$application,, $analysis] = $this->createAssistantAnalysis([
            'ocr_text' => 'curto',
            'ocr_quality_score' => '0.20',
        ]);
        $originalStatus = $application->status;

        $score = app(DocumentAiAssistantPipeline::class)->process($analysis);

        $this->assertDatabaseHas('document_ai_scores', [
            'id' => $score->id,
            'document_ai_analysis_id' => $analysis->id,
        ]);
        $this->assertDatabaseHas('document_ai_flags', [
            'document_ai_analysis_id' => $analysis->id,
            'code' => 'insufficient_ocr',
        ]);
        $this->assertDatabaseHas('document_ai_suggestions', [
            'document_ai_analysis_id' => $analysis->id,
            'flag_code' => 'insufficient_ocr',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $score->getMorphClass(),
            'auditable_id' => $score->id,
            'action' => 'document_ai_score_calculated',
        ]);
        $this->assertSame($originalStatus, $application->fresh()->status);

        Event::assertDispatched(DocumentAiScoreCalculationStarted::class);
        Event::assertDispatched(DocumentAiScoreCalculated::class);
        Event::assertDispatched(DocumentAiRiskFlagDetected::class);
        Event::assertDispatched(DocumentAiSuggestionGenerated::class);
    }
}
