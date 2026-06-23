<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;
use Illuminate\Support\Carbon;

class DocumentQualityAnalyzer
{
    public function __construct(
        private readonly DocumentExtractionSchemaRegistry $schemaRegistry,
    ) {}

    /**
     * @return list<DocumentAiRiskFlag>
     */
    public function analyze(DocumentAiAnalysis $analysis): array
    {
        $analysis->loadMissing('fields');

        $flags = [];
        $text = trim((string) ($analysis->ocr_text ?: $analysis->getAttribute('raw_text') ?: ''));
        $stats = $this->textStats($text);
        $quality = is_numeric($analysis->getAttribute('ocr_quality_score')) ? (float) $analysis->getAttribute('ocr_quality_score') : null;

        if ((int) ($analysis->source_size_bytes ?? 0) === 0 || ($text === '' && $analysis->ocr_status === DocumentAiOcrStatus::Completed)) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::EmptyDocument, DocumentAiRiskSeverity::Critical, 'Não foi identificado conteúdo útil no documento.', 'document_quality_analyzer', 0.95);
        }

        if ($analysis->ocr_status === DocumentAiOcrStatus::Failed || $analysis->ocr_status === DocumentAiOcrStatus::Unavailable) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::DocumentUnreadable, DocumentAiRiskSeverity::Critical, 'O documento não ficou legível para análise automática.', 'document_quality_analyzer', 0.90);
        }

        if ($analysis->ocr_available && (
            $stats['characters'] < (int) config('document-ai-score.thresholds.minimum_ocr_text_characters', 80)
            || $stats['words'] < (int) config('document-ai-score.thresholds.minimum_ocr_words', 12)
            || ($quality !== null && $quality < (float) config('document-ai-score.thresholds.low_ocr_quality', 0.55))
        )) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::InsufficientOcr, DocumentAiRiskSeverity::High, 'O OCR produzido é insuficiente para validação automática robusta.', 'document_quality_analyzer', 0.88);
        }

        if ($this->hasPageCropSignal($analysis)) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::PageCropped, DocumentAiRiskSeverity::Medium, 'Foram detetados sinais de página cortada ou incompleta.', 'document_quality_analyzer', 0.75);
        }

        if ($this->hasMissingRequiredFields($analysis)) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::MissingRequiredFields, DocumentAiRiskSeverity::High, 'Existem campos obrigatórios que não foram extraídos com confiança suficiente.', 'document_quality_analyzer', 0.84);
        }

        if ($this->hasExpiredDate($analysis)) {
            $flags[] = $this->flag(DocumentAiRiskFlagCode::DocumentExpired, DocumentAiRiskSeverity::High, 'Foi identificada uma data de validade anterior à data atual.', 'document_quality_analyzer', 0.82);
        }

        return $flags;
    }

    /**
     * @return array{characters: int, words: int}
     */
    private function textStats(string $text): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'characters' => mb_strlen($text),
            'words' => count($words),
        ];
    }

    private function hasPageCropSignal(DocumentAiAnalysis $analysis): bool
    {
        $signals = $analysis->classification_signals;

        if (! is_array($signals)) {
            return false;
        }

        return (bool) ($signals['page_cropped'] ?? $signals['cropped'] ?? $signals['incomplete_page'] ?? false);
    }

    private function hasMissingRequiredFields(DocumentAiAnalysis $analysis): bool
    {
        if ($analysis->detected_document_type === null) {
            return false;
        }

        $schema = $this->schemaRegistry->schemaFor($analysis->detected_document_type);

        if ($schema === null) {
            return false;
        }

        $present = $analysis->fields
            ->filter(fn (DocumentAiField $field): bool => trim((string) ($field->normalized_value ?: $field->value)) !== '')
            ->pluck('key')
            ->all();

        foreach ($schema->fields as $key => $definition) {
            if ($definition['required'] && ! in_array($key, $present, true)) {
                return true;
            }
        }

        return false;
    }

    private function hasExpiredDate(DocumentAiAnalysis $analysis): bool
    {
        $candidateKeys = [
            'validade',
            'valid_until',
            'expiry_date',
            'expiration_date',
            'validity_date',
            'document_valid_until',
        ];

        foreach ($analysis->fields as $field) {
            if (! in_array((string) $field->key, $candidateKeys, true)) {
                continue;
            }

            $value = trim((string) ($field->normalized_value ?: $field->value));

            if ($value === '') {
                continue;
            }

            try {
                if (Carbon::parse($value)->endOfDay()->isPast()) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }

    private function flag(
        DocumentAiRiskFlagCode $code,
        DocumentAiRiskSeverity $severity,
        string $message,
        string $detectedBy,
        float $confidence,
    ): DocumentAiRiskFlag {
        return new DocumentAiRiskFlag(
            code: $code,
            severity: $severity,
            scoreImpact: (int) config("document-ai-score.penalties.{$code->value}", 0),
            message: $message,
            detectedBy: $detectedBy,
            confidence: $confidence,
            suggestionTemplate: $code->value,
        );
    }
}
