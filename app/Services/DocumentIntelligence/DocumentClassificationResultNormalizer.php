<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\AiClassificationResult;
use App\Enums\DocumentAiDocumentType;
use Throwable;

class DocumentClassificationResultNormalizer
{
    public function fromJson(?string $json, string $source = 'ollama'): AiClassificationResult
    {
        if ($json === null || trim($json) === '') {
            return $this->failed('empty_ai_response', $source);
        }

        try {
            $decoded = json_decode($this->extractJsonObject($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $this->failed('invalid_ai_json', $source, ['raw_length' => mb_strlen($json)]);
        }

        if (! is_array($decoded)) {
            return $this->failed('invalid_ai_payload', $source);
        }

        $type = $this->documentType($decoded['document_type'] ?? null);

        if (! $type instanceof DocumentAiDocumentType) {
            return $this->failed('unsupported_ai_document_type', $source, ['document_type' => $decoded['document_type'] ?? null]);
        }

        $confidence = $this->confidence($decoded['confidence'] ?? null);

        return new AiClassificationResult(
            documentType: $type,
            label: is_string($decoded['label'] ?? null) ? $decoded['label'] : $type->label(),
            confidence: $confidence,
            signals: ['ai:'.$source.':json'],
            requiresManualReview: $confidence < (float) config('document-ai-classification.thresholds.auto_classification', 0.90),
            source: $source,
            reason: is_string($decoded['reason'] ?? null) ? $decoded['reason'] : null,
            rawResponse: $this->minimizedRaw($decoded),
        );
    }

    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    private function failed(string $code, string $source, ?array $rawResponse = null): AiClassificationResult
    {
        return new AiClassificationResult(
            documentType: null,
            label: null,
            confidence: 0.0,
            signals: ['ai:'.$source.':'.$code],
            requiresManualReview: true,
            source: $source,
            reason: $code,
            rawResponse: $rawResponse,
            failureCode: $code,
        );
    }

    private function documentType(mixed $value): ?DocumentAiDocumentType
    {
        return is_string($value) ? DocumentAiDocumentType::tryFrom($value) : null;
    }

    private function confidence(mixed $value): float
    {
        if (is_numeric($value)) {
            return round(max(0.0, min(1.0, (float) $value)), 2);
        }

        return 0.0;
    }

    private function extractJsonObject(string $response): string
    {
        $response = trim($response);
        $start = strpos($response, '{');
        $end = strrpos($response, '}');

        if ($start === false || $end === false || $end < $start) {
            return $response;
        }

        return substr($response, $start, ($end - $start) + 1);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function minimizedRaw(array $payload): array
    {
        return array_intersect_key($payload, array_flip([
            'document_type',
            'label',
            'confidence',
            'reason',
        ]));
    }
}
