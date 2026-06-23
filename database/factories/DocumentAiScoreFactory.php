<?php

namespace Database\Factories;

use App\Enums\DocumentAiScoreLabel;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiScore>
 */
class DocumentAiScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'score' => 88,
            'label' => DocumentAiScoreLabel::ConfiavelComAtencao->value,
            'components' => [
                'ocr' => 18,
                'classification' => 19,
                'extraction' => 17,
                'consistency' => 22,
                'risk' => 12,
            ],
            'explanation' => [
                'positives' => ['OCR disponível', 'Classificação consistente'],
                'attention' => ['Rever indicadores antes de decisão técnica.'],
                'recommendations' => ['Validar manualmente os dados principais.'],
            ],
            'summary' => 'Documento com confiança elevada e indicadores para atenção técnica.',
            'requires_manual_review' => false,
            'calculated_at' => now(),
        ];
    }
}
