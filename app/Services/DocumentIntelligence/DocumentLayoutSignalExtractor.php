<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\LayoutSignalResult;
use App\Enums\DocumentAiDocumentType;
use Illuminate\Support\Str;

class DocumentLayoutSignalExtractor
{
    public function extract(string $text): LayoutSignalResult
    {
        $normalized = Str::lower(Str::ascii($text));
        $scores = [];
        $signals = [];

        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::Iban, preg_match('/\b[A-Z]{2}\d{2}[A-Z0-9]{10,30}\b/i', $text) === 1, 'layout:iban_pattern', 3);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::CartaoCidadao, preg_match('/\b\d{8}\s?\d?\s?[A-Z0-9]{2}\d\b/i', $text) === 1, 'layout:civil_id_pattern', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::Passaporte, preg_match('/\b(passport|passaporte).{0,40}\b[A-Z]{1,2}\d{6,8}\b/i', $text) === 1, 'layout:passport_number', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::Irs, str_contains($normalized, 'modelo 3') && str_contains($normalized, 'anexo'), 'layout:irs_model3_annex', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::NotaLiquidacao, str_contains($normalized, 'demonstracao de liquidacao') || str_contains($normalized, 'nota de liquidacao'), 'layout:tax_liquidation_title', 3);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::ReciboVencimento, str_contains($normalized, 'salario') || str_contains($normalized, 'remuneracao iliquida') || str_contains($normalized, 'liquido a receber'), 'layout:salary_columns', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::ContratoArrendamento, str_contains($normalized, 'clausula') && str_contains($normalized, 'renda'), 'layout:contract_clauses', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::AtestadoMultiusos, str_contains($normalized, '%') && str_contains($normalized, 'incapacidade'), 'layout:disability_percentage', 2);
        $this->scoreWhen($scores, $signals, DocumentAiDocumentType::CertidaoEscolar, str_contains($normalized, 'ano letivo') || str_contains($normalized, 'matricula'), 'layout:school_year', 2);

        if ($scores === []) {
            return new LayoutSignalResult(null, 0.0, ['layout:no_signal'], []);
        }

        arsort($scores);
        $type = array_key_first($scores);
        $confidence = min(0.90, 0.40 + (((int) $scores[$type]) * 0.16));

        return new LayoutSignalResult(
            documentType: DocumentAiDocumentType::from((string) $type),
            confidence: round($confidence, 2),
            signals: array_values(array_unique($signals)),
            scores: $scores,
        );
    }

    /**
     * @param  array<string, int>  $scores
     * @param  list<string>  $signals
     */
    private function scoreWhen(array &$scores, array &$signals, DocumentAiDocumentType $type, bool $condition, string $signal, int $weight): void
    {
        if (! $condition) {
            return;
        }

        $scores[$type->value] = ($scores[$type->value] ?? 0) + $weight;
        $signals[] = $signal;
    }
}
