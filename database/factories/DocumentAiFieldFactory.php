<?php

namespace Database\Factories;

use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiField>
 */
class DocumentAiFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_ai_analysis_id' => DocumentAiAnalysis::factory(),
            'document_type' => null,
            'key' => 'document_type_hint',
            'label' => 'Tipo documental indicado',
            'value' => 'Documento fictício',
            'normalized_value' => 'documento_ficticio',
            'value_type' => 'string',
            'confidence' => '0.00',
            'source' => 'regex',
            'requires_review' => false,
            'page' => null,
            'bbox' => null,
            'metadata' => ['source' => 'factory'],
        ];
    }
}
