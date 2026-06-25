<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Enums\DocumentAiScoreLabel;
use App\Services\DocumentIntelligence\DocumentAiRiskScoringService;
use Tests\TestCase;

class DocumentAiScoreServiceTest extends TestCase
{
    public function test_score_result_maps_to_assistive_green_yellow_red_labels(): void
    {
        $service = app(DocumentAiRiskScoringService::class);

        $this->assertSame('green', $service->labelForResult(new DocumentAiScoreResult(
            score: 91,
            label: DocumentAiScoreLabel::MuitoConfiavel,
            components: [],
            summary: 'Sintético.',
            explanation: [],
            requiresManualReview: false,
        )));

        $this->assertSame('yellow', $service->labelForResult(new DocumentAiScoreResult(
            score: 74,
            label: DocumentAiScoreLabel::RequerRevisao,
            components: [],
            summary: 'Sintético.',
            explanation: [],
            requiresManualReview: true,
        )));

        $this->assertSame('red', $service->labelForResult(new DocumentAiScoreResult(
            score: 20,
            label: DocumentAiScoreLabel::Critico,
            components: [],
            summary: 'Sintético.',
            explanation: [],
            requiresManualReview: true,
        )));
    }
}
