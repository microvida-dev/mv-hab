<?php

namespace Database\Factories;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiSuggestionStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiScore;
use App\Models\DocumentAiSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiSuggestion>
 */
class DocumentAiSuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'document_ai_score_id' => fn (array $attributes): int => DocumentAiScore::factory()->create([
                'document_ai_analysis_id' => $attributes['document_ai_analysis_id'],
            ])->id,
            'flag_code' => DocumentAiRiskFlagCode::InsufficientOcr->value,
            'severity' => DocumentAiRiskSeverity::Medium->value,
            'status' => DocumentAiSuggestionStatus::Draft->value,
            'suggestion' => 'Solicita-se o envio de uma versão mais legível do documento para conclusão da análise técnica.',
            'metadata' => ['source' => 'factory'],
        ];
    }
}
