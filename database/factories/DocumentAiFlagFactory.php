<?php

namespace Database\Factories;

use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiFlag>
 */
class DocumentAiFlagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'code' => 'manual_review_required',
            'severity' => 'medium',
            'message' => 'Documento sinalizado para revisão manual.',
            'details' => ['source' => 'factory'],
            'requires_manual_review' => true,
        ];
    }
}
