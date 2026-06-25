<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Models\DocumentAiScore;
use App\Services\DocumentIntelligence\DocumentAiRiskScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAiRiskScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_ai_score_maps_to_green_yellow_and_red_without_administrative_decision(): void
    {
        $service = app(DocumentAiRiskScoringService::class);

        $this->assertSame('green', $service->labelForScore(88, false));
        $this->assertSame('yellow', $service->labelForScore(72, true));
        $this->assertSame('red', $service->labelForScore(35, true));
    }

    public function test_model_score_label_uses_manual_review_as_yellow_risk(): void
    {
        $score = DocumentAiScore::factory()->create([
            'score' => 82,
            'requires_manual_review' => true,
        ]);

        $this->assertSame('yellow', app(DocumentAiRiskScoringService::class)->labelForModel($score));
    }
}
