<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Data\DocumentIntelligence\DocumentAiScoreResult;

class DocumentAiScoreExplainer
{
    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     * @return array<string, mixed>
     */
    public function explain(DocumentAiScoreResult $result, array $flags): array
    {
        $positives = [];
        $attention = [];
        $recommendations = [];

        if (($result->components['ocr'] ?? 0) >= 16) {
            $positives[] = 'OCR Excelente';
        }

        if (($result->components['classification'] ?? 0) >= 16) {
            $positives[] = 'Classificação correta';
        }

        if (($result->components['extraction'] ?? 0) >= 16) {
            $positives[] = 'Campos estruturados disponíveis';
        }

        foreach ($flags as $flag) {
            $attention[] = $flag->code->label();
        }

        if ($result->requiresManualReview) {
            $recommendations[] = 'Rever manualmente';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Validar no fluxo técnico normal.';
        }

        return [
            'positives' => $positives,
            'attention' => array_values(array_unique($attention)),
            'recommendations' => $recommendations,
            'components' => $result->components,
        ];
    }
}
