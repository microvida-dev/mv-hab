<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentAiRiskFlag;
use App\Data\DocumentIntelligence\DocumentAiScoreResult;
use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;
use App\Enums\DocumentAiOcrStatus;
use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiScoreLabel;
use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentAiField;

class DocumentAiScoreCalculator
{
    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    public function calculate(DocumentAiAnalysis $analysis, array $flags): DocumentAiScoreResult
    {
        $analysis->loadMissing(['fields', 'validations']);

        $components = [
            'ocr' => $this->ocrScore($analysis, $flags),
            'classification' => $this->classificationScore($analysis),
            'extraction' => $this->extractionScore($analysis, $flags),
            'consistency' => $this->consistencyScore($analysis),
            'risk' => $this->riskScore($flags),
        ];

        $penalty = array_sum(array_map(
            static fn (DocumentAiRiskFlag $flag): int => max(0, $flag->scoreImpact),
            $flags
        ));
        $score = $this->clamp(array_sum($components) - min(85, $penalty));
        $label = DocumentAiScoreLabel::fromScore($score);
        $requiresReview = $score < (int) config('document-ai-score.thresholds.manual_review_score_below', 75)
            || collect($flags)->contains(fn (DocumentAiRiskFlag $flag): bool => $flag->requiresManualReview);

        return new DocumentAiScoreResult(
            score: $score,
            label: $label,
            components: $components + ['penalty' => min(85, $penalty)],
            summary: $this->summary($label, $requiresReview),
            explanation: [],
            requiresManualReview: $requiresReview,
        );
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function ocrScore(DocumentAiAnalysis $analysis, array $flags): int
    {
        if ($this->hasFlag($flags, DocumentAiRiskFlagCode::EmptyDocument, DocumentAiRiskFlagCode::DocumentUnreadable)) {
            return 0;
        }

        if ($analysis->ocr_status !== DocumentAiOcrStatus::Completed || ! $analysis->ocr_available) {
            return 4;
        }

        $quality = is_numeric($analysis->getAttribute('ocr_quality_score')) ? (float) $analysis->getAttribute('ocr_quality_score') : 0.85;
        $score = (int) round($quality * (int) config('document-ai-score.weights.ocr', 20));

        if ($this->hasFlag($flags, DocumentAiRiskFlagCode::InsufficientOcr)) {
            $score = min($score, 8);
        }

        return $this->clampComponent($score, 'ocr');
    }

    private function classificationScore(DocumentAiAnalysis $analysis): int
    {
        if ($analysis->classification_status !== DocumentAiClassificationStatus::Completed || $analysis->detected_document_type === null) {
            return 4;
        }

        if ($analysis->detected_document_type === DocumentAiDocumentType::Outro || $analysis->classification_requires_manual_review) {
            return 8;
        }

        $confidence = is_numeric($analysis->classification_confidence) ? (float) $analysis->classification_confidence : 0.80;

        return $this->clampComponent((int) round($confidence * (int) config('document-ai-score.weights.classification', 20)), 'classification');
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function extractionScore(DocumentAiAnalysis $analysis, array $flags): int
    {
        if ($analysis->extraction_status !== DocumentAiExtractionStatus::Completed) {
            return 5;
        }

        $confidence = is_numeric($analysis->extraction_confidence)
            ? (float) $analysis->extraction_confidence
            : $this->averageFieldConfidence($analysis);

        $score = (int) round($confidence * (int) config('document-ai-score.weights.extraction', 20));

        if ($analysis->extraction_requires_manual_review || $this->hasFlag($flags, DocumentAiRiskFlagCode::MissingRequiredFields)) {
            $score = min($score, 10);
        }

        return $this->clampComponent($score, 'extraction');
    }

    private function consistencyScore(DocumentAiAnalysis $analysis): int
    {
        $validations = $analysis->validations;

        if ($validations->isEmpty()) {
            return 18;
        }

        $score = (int) config('document-ai-score.weights.consistency', 25);

        foreach ($validations as $validation) {
            $score -= match ($validation->severity) {
                DocumentAiValidationSeverity::Critical => 15,
                DocumentAiValidationSeverity::Medium => 8,
                DocumentAiValidationSeverity::Light => 3,
                default => in_array($validation->status, [
                    DocumentAiValidationStatus::Inconclusive,
                    DocumentAiValidationStatus::MissingCandidateValue,
                    DocumentAiValidationStatus::MissingDocumentValue,
                ], true) ? 5 : 0,
            };
        }

        return max(0, min((int) config('document-ai-score.weights.consistency', 25), $score));
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function riskScore(array $flags): int
    {
        $weight = (int) config('document-ai-score.weights.risk', 15);
        $penalty = array_sum(array_map(
            static fn (DocumentAiRiskFlag $flag): int => max(0, $flag->scoreImpact),
            $flags
        ));

        return max(0, $weight - min($weight, (int) round($penalty / 5)));
    }

    private function averageFieldConfidence(DocumentAiAnalysis $analysis): float
    {
        $values = $analysis->fields
            ->map(fn (DocumentAiField $field): ?float => is_numeric($field->confidence) ? (float) $field->confidence : null)
            ->filter(fn (?float $value): bool => $value !== null);

        if ($values->isEmpty()) {
            return 0.70;
        }

        return (float) $values->avg();
    }

    /**
     * @param  list<DocumentAiRiskFlag>  $flags
     */
    private function hasFlag(array $flags, DocumentAiRiskFlagCode ...$codes): bool
    {
        $values = array_map(static fn (DocumentAiRiskFlagCode $code): string => $code->value, $codes);

        return collect($flags)->contains(fn (DocumentAiRiskFlag $flag): bool => in_array($flag->code->value, $values, true));
    }

    private function clampComponent(int $score, string $component): int
    {
        return max(0, min((int) config("document-ai-score.weights.{$component}", 20), $score));
    }

    private function clamp(int $score): int
    {
        return max(0, min(100, $score));
    }

    private function summary(DocumentAiScoreLabel $label, bool $requiresReview): string
    {
        if ($requiresReview) {
            return "Score {$label->label()} com indicadores que recomendam revisão manual antes de qualquer decisão técnica.";
        }

        return "Score {$label->label()} sem indicadores críticos no processamento automático.";
    }
}
