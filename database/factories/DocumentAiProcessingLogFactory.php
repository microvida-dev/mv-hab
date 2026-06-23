<?php

namespace Database\Factories;

use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiProcessingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiProcessingLog>
 */
class DocumentAiProcessingLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'step' => 'queued',
            'level' => 'info',
            'message' => 'Análise documental preparada.',
            'context' => ['schema_version' => 'sprint27.infrastructure.v1'],
            'duration_ms' => null,
            'created_at' => now(),
        ];
    }
}
