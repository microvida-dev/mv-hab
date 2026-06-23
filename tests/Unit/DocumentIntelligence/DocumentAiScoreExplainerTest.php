<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiScoreLabel;
use App\Services\DocumentIntelligence\DocumentAiScoreExplainer;
use Tests\TestCase;

class DocumentAiScoreExplainerTest extends TestCase
{
    public function test_it_produces_operational_explanation_without_accusatory_language(): void
    {
        $result = new DocumentAiScoreResult(
            score: 68,
            label: DocumentAiScoreLabel::RequerRevisao,
            components: ['ocr' => 18, 'classification' => 18, 'extraction' => 15, 'consistency' => 10, 'risk' => 7],
            summary: 'Score requer revisão.',
            explanation: [],
            requiresManualReview: true,
        );

        $explanation = app(DocumentAiScoreExplainer::class)->explain($result, [
            new DocumentAiRiskFlag(DocumentAiRiskFlagCode::IncomeIncompatible, DocumentAiRiskSeverity::High, 25, 'Diferença de teste.', 'test', 0.8),
        ]);
        $text = implode(' ', array_merge($explanation['attention'], $explanation['recommendations']));

        $this->assertContains('OCR Excelente', $explanation['positives']);
        $this->assertContains('Rever manualmente', $explanation['recommendations']);
        $this->assertStringNotContainsString('fraude confirmada', mb_strtolower($text));
    }
}
