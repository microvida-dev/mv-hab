<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\AiClassificationResult;
use App\Data\DocumentIntelligence\DocumentClassificationResult;
use App\Data\DocumentIntelligence\KeywordClassificationResult;
use App\Data\DocumentIntelligence\LayoutSignalResult;
use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;

class DocumentClassificationScorer
{
    public function score(
        KeywordClassificationResult $keyword,
        LayoutSignalResult $layout,
        AiClassificationResult $ai,
    ): DocumentClassificationResult {
        $candidates = [];
        $signals = [...$keyword->signals, ...$layout->signals, ...$ai->signals];

        $this->addCandidate($candidates, $keyword->documentType, $keyword->confidence, 0.70);

        if ($layout->documentType instanceof DocumentAiDocumentType) {
            $this->addCandidate($candidates, $layout->documentType, $layout->confidence, 0.20);
        }

        if ($ai->documentType instanceof DocumentAiDocumentType && $ai->failureCode === null) {
            $this->addCandidate($candidates, $ai->documentType, $ai->confidence, 0.30);
        }

        arsort($candidates);
        $type = array_key_first($candidates);
        $documentType = is_string($type) ? DocumentAiDocumentType::from($type) : DocumentAiDocumentType::Outro;
        $confidence = $this->confidenceFor($documentType, $keyword, $layout, $ai, (float) ($candidates[$documentType->value] ?? 0.0));
        $source = $this->source($keyword, $layout, $ai);
        $manualThreshold = (float) config('document-ai-classification.thresholds.manual_review', 0.70);
        $autoThreshold = (float) config('document-ai-classification.thresholds.auto_classification', 0.90);
        $requiresManualReview = $documentType === DocumentAiDocumentType::Outro || $confidence < $autoThreshold || $ai->requiresManualReview;
        $status = match (true) {
            $documentType === DocumentAiDocumentType::Outro => DocumentAiClassificationStatus::ManualReview,
            $confidence < $manualThreshold => DocumentAiClassificationStatus::LowConfidence,
            $requiresManualReview => DocumentAiClassificationStatus::ManualReview,
            default => DocumentAiClassificationStatus::Completed,
        };

        return new DocumentClassificationResult(
            documentType: $documentType,
            label: $documentType->label(),
            confidence: $confidence,
            source: $source,
            signals: array_values(array_unique($signals)),
            requiresManualReview: $requiresManualReview,
            status: $status,
            raw: [
                'keyword' => [
                    'type' => $keyword->documentType->value,
                    'confidence' => $keyword->confidence,
                    'scores' => $keyword->scores,
                ],
                'layout' => [
                    'type' => $layout->documentType?->value,
                    'confidence' => $layout->confidence,
                    'scores' => $layout->scores,
                ],
                'ai' => [
                    'type' => $ai->documentType?->value,
                    'confidence' => $ai->confidence,
                    'failure_code' => $ai->failureCode,
                    'reason' => $ai->reason,
                    'raw' => $ai->rawResponse,
                ],
            ],
        );
    }

    /**
     * @param  array<string, float>  $candidates
     */
    private function addCandidate(array &$candidates, DocumentAiDocumentType $type, float $confidence, float $weight): void
    {
        $candidates[$type->value] = ($candidates[$type->value] ?? 0.0) + ($confidence * $weight);
    }

    private function confidenceFor(
        DocumentAiDocumentType $documentType,
        KeywordClassificationResult $keyword,
        LayoutSignalResult $layout,
        AiClassificationResult $ai,
        float $weightedScore,
    ): float {
        $matchingConfidences = [$weightedScore];

        if ($keyword->documentType === $documentType) {
            $matchingConfidences[] = $keyword->confidence;
        }

        if ($layout->documentType === $documentType) {
            $matchingConfidences[] = $layout->confidence;
        }

        if ($ai->documentType === $documentType && $ai->failureCode === null) {
            $matchingConfidences[] = $ai->confidence;
        }

        return round(max(0.0, min(0.99, max($matchingConfidences))), 2);
    }

    private function source(
        KeywordClassificationResult $keyword,
        LayoutSignalResult $layout,
        AiClassificationResult $ai,
    ): string {
        $sources = ['ocr', 'keywords'];

        if ($layout->documentType instanceof DocumentAiDocumentType) {
            $sources[] = 'layout';
        }

        if ($ai->documentType instanceof DocumentAiDocumentType && $ai->failureCode === null) {
            $sources[] = 'local_ai';
        }

        return implode('+', $sources);
    }
}
