<?php

namespace App\Services\DocumentIntelligence;

use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiValidationStatus;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DocumentValidationComparator
{
    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    public function compare(mixed $candidateValue, mixed $documentValue, DocumentAiComparisonMethod $method): array
    {
        if ($this->blank($candidateValue) && $this->blank($documentValue)) {
            return $this->result(DocumentAiValidationStatus::Inconclusive, 0.0, ['reason' => 'both_values_missing']);
        }

        if ($this->blank($candidateValue)) {
            return $this->result(DocumentAiValidationStatus::MissingCandidateValue, 0.0, ['reason' => 'candidate_value_missing']);
        }

        if ($this->blank($documentValue)) {
            return $this->result(DocumentAiValidationStatus::MissingDocumentValue, 0.0, ['reason' => 'document_value_missing']);
        }

        return match ($method) {
            DocumentAiComparisonMethod::Exact => $this->exact($candidateValue, $documentValue),
            DocumentAiComparisonMethod::NormalizedExact => $this->normalizedExact($candidateValue, $documentValue),
            DocumentAiComparisonMethod::FuzzyName => $this->similarity($candidateValue, $documentValue, 'name_similarity_match', 'name_similarity_partial'),
            DocumentAiComparisonMethod::Date => $this->date($candidateValue, $documentValue),
            DocumentAiComparisonMethod::MoneyTolerance => $this->money($candidateValue, $documentValue),
            DocumentAiComparisonMethod::AddressSimilarity => $this->similarity($candidateValue, $documentValue, 'address_similarity_match', 'address_similarity_partial'),
            DocumentAiComparisonMethod::DocumentConsistency => $this->documentConsistency($candidateValue, $documentValue),
            DocumentAiComparisonMethod::Manual => $this->result(DocumentAiValidationStatus::ManualReview, 0.0, ['reason' => 'manual_rule']),
        };
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function exact(mixed $candidateValue, mixed $documentValue): array
    {
        $match = (string) $candidateValue === (string) $documentValue;

        return $this->result($match ? DocumentAiValidationStatus::Match : DocumentAiValidationStatus::Mismatch, $match ? 1.0 : 0.0);
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function normalizedExact(mixed $candidateValue, mixed $documentValue): array
    {
        $candidate = $this->identifier($candidateValue);
        $document = $this->identifier($documentValue);
        $match = $candidate !== '' && $candidate === $document;

        return $this->result($match ? DocumentAiValidationStatus::Match : DocumentAiValidationStatus::Mismatch, $match ? 1.0 : 0.0, [
            'normalized_candidate_length' => strlen($candidate),
            'normalized_document_length' => strlen($document),
        ]);
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function similarity(mixed $candidateValue, mixed $documentValue, string $matchKey, string $partialKey): array
    {
        $candidate = $this->text($candidateValue);
        $document = $this->text($documentValue);

        if ($candidate === '' || $document === '') {
            return $this->result(DocumentAiValidationStatus::Inconclusive, 0.0, ['reason' => 'normalized_text_empty']);
        }

        similar_text($candidate, $document, $percent);
        $score = round($percent / 100, 4);
        $match = (float) config("document-ai-validation.thresholds.{$matchKey}", 0.9);
        $partial = (float) config("document-ai-validation.thresholds.{$partialKey}", 0.75);

        if ($score >= $match) {
            return $this->result(DocumentAiValidationStatus::Match, $score, ['similarity' => $score]);
        }

        if ($score >= $partial || $this->tokenOverlap($candidate, $document) >= 0.66) {
            return $this->result(DocumentAiValidationStatus::PartialMatch, $score, ['similarity' => $score]);
        }

        return $this->result(DocumentAiValidationStatus::Mismatch, $score, ['similarity' => $score]);
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function date(mixed $candidateValue, mixed $documentValue): array
    {
        $candidate = $this->dateString($candidateValue);
        $document = $this->dateString($documentValue);

        if ($candidate === null || $document === null) {
            return $this->result(DocumentAiValidationStatus::Inconclusive, 0.0, ['reason' => 'date_parse_failed']);
        }

        $match = $candidate === $document;

        return $this->result($match ? DocumentAiValidationStatus::Match : DocumentAiValidationStatus::Mismatch, $match ? 1.0 : 0.0, [
            'candidate_date' => $candidate,
            'document_date' => $document,
        ]);
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function money(mixed $candidateValue, mixed $documentValue): array
    {
        $candidate = $this->float($candidateValue);
        $document = $this->float($documentValue);

        if ($candidate === null || $document === null || $candidate <= 0.0) {
            return $this->result(DocumentAiValidationStatus::Inconclusive, 0.0, ['reason' => 'money_parse_failed']);
        }

        $difference = abs($document - $candidate);
        $differencePercent = round(($difference / $candidate) * 100, 2);
        $lightTolerance = (float) config('document-ai-validation.thresholds.money_light_tolerance_percent', 5);
        $mediumTolerance = (float) config('document-ai-validation.thresholds.money_medium_tolerance_percent', 15);

        if ($differencePercent <= $lightTolerance) {
            return $this->result(DocumentAiValidationStatus::Match, 1.0, [
                'difference_percent' => $differencePercent,
                'document_higher_than_declared' => $document > $candidate,
            ]);
        }

        if ($differencePercent <= $mediumTolerance) {
            return $this->result(DocumentAiValidationStatus::PartialMatch, max(0.5, 1 - ($differencePercent / 100)), [
                'difference_percent' => $differencePercent,
                'document_higher_than_declared' => $document > $candidate,
            ]);
        }

        return $this->result(DocumentAiValidationStatus::Mismatch, max(0.0, 1 - ($differencePercent / 100)), [
            'difference_percent' => $differencePercent,
            'document_higher_than_declared' => $document > $candidate,
        ]);
    }

    /**
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function documentConsistency(mixed $candidateValue, mixed $documentValue): array
    {
        $candidate = $this->float($candidateValue);
        $document = $this->float($documentValue);

        if ($candidate === null || $document === null) {
            return $this->result(DocumentAiValidationStatus::Inconclusive, 0.0, ['reason' => 'consistency_value_missing']);
        }

        $match = $document <= $candidate + 0.01;

        return $this->result($match ? DocumentAiValidationStatus::Match : DocumentAiValidationStatus::Mismatch, $match ? 1.0 : 0.0, [
            'document_value' => $document,
            'candidate_value' => $candidate,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{status: DocumentAiValidationStatus, confidence: float, metadata: array<string, mixed>}
     */
    private function result(DocumentAiValidationStatus $status, float $confidence, array $metadata = []): array
    {
        return [
            'status' => $status,
            'confidence' => round(max(0.0, min(1.0, $confidence)), 2),
            'metadata' => $metadata,
        ];
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '' || (is_string($value) && trim($value) === '');
    }

    private function text(mixed $value): string
    {
        $text = Str::ascii((string) $value);
        $text = Str::lower($text);
        $text = preg_replace('/[^a-z0-9 ]+/i', ' ', $text) ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    private function identifier(mixed $value): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '', Str::upper((string) $value)) ?? '';
    }

    private function tokenOverlap(string $candidate, string $document): float
    {
        $candidateTokens = array_values(array_filter(explode(' ', $candidate)));
        $documentTokens = array_values(array_filter(explode(' ', $document)));

        if ($candidateTokens === [] || $documentTokens === []) {
            return 0.0;
        }

        $intersect = array_intersect($candidateTokens, $documentTokens);

        return count($intersect) / max(count($candidateTokens), count($documentTokens));
    }

    private function dateString(mixed $value): ?string
    {
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function float(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', "\xc2\xa0"], '', (string) $value);
        $normalized = preg_replace('/[^0-9,.\-]/', '', $normalized) ?? '';

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
