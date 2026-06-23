<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionResult;
use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionStatus;

class DocumentExtractionScorer
{
    /**
     * @param  list<ExtractedDocumentField>  $fields
     * @param  list<DocumentExtractionFlag>  $flags
     */
    public function score(DocumentExtractionSchema $schema, array $fields, array $flags, string $source): DocumentExtractionResult
    {
        $fieldReviewThreshold = (float) config('document-ai-extraction.thresholds.field_review', 0.75);
        $documentReviewThreshold = (float) config('document-ai-extraction.thresholds.document_review', 0.80);
        $scoredFields = [];

        foreach ($fields as $field) {
            $requiresReview = $field->requiresReview;
            $hasValue = $field->value !== null && $field->value !== '';

            if ($hasValue && $field->confidence < $fieldReviewThreshold) {
                $requiresReview = true;
                $flags[] = new DocumentExtractionFlag(
                    code: 'low_confidence_field',
                    severity: 'medium',
                    message: 'Campo extraído com confiança abaixo do limiar configurado.',
                    field: $field->key,
                    details: ['confidence' => $field->confidence],
                );
            }

            if (! $hasValue && (bool) ($schema->fields[$field->key]['required'] ?? false)) {
                $requiresReview = true;
            }

            $scoredFields[] = new ExtractedDocumentField(
                key: $field->key,
                label: $field->label,
                type: $field->type,
                value: $field->value,
                normalizedValue: $field->normalizedValue,
                confidence: $field->confidence,
                source: $field->source,
                requiresReview: $requiresReview,
                sensitive: $field->sensitive,
                healthData: $field->healthData,
                page: $field->page,
            );
        }

        $confidence = $this->globalConfidence($scoredFields);
        $requiresManualReview = $this->requiresManualReview($scoredFields, $confidence, $documentReviewThreshold);
        $status = $this->status($scoredFields, $confidence, $documentReviewThreshold, $requiresManualReview);

        return new DocumentExtractionResult(
            schemaVersion: (string) config('document-ai-extraction.schema_version', '1.0'),
            documentType: $schema->documentType,
            status: $status,
            confidence: $confidence,
            fields: $scoredFields,
            flags: $flags,
            requiresManualReview: $requiresManualReview,
            source: $source,
        );
    }

    /**
     * @param  list<ExtractedDocumentField>  $fields
     */
    private function globalConfidence(array $fields): float
    {
        $values = array_values(array_filter(
            $fields,
            static fn (ExtractedDocumentField $field): bool => $field->value !== null && $field->value !== ''
        ));

        if ($values === []) {
            return 0.0;
        }

        $sum = array_reduce(
            $values,
            static fn (float $carry, ExtractedDocumentField $field): float => $carry + $field->confidence,
            0.0
        );

        return round($sum / count($values), 2);
    }

    /**
     * @param  list<ExtractedDocumentField>  $fields
     */
    private function requiresManualReview(array $fields, float $confidence, float $threshold): bool
    {
        if ($confidence < $threshold) {
            return true;
        }

        foreach ($fields as $field) {
            if ($field->requiresReview) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ExtractedDocumentField>  $fields
     */
    private function status(array $fields, float $confidence, float $threshold, bool $requiresManualReview): DocumentAiExtractionStatus
    {
        $hasAnyValue = false;

        foreach ($fields as $field) {
            if ($field->value !== null && $field->value !== '') {
                $hasAnyValue = true;
                break;
            }
        }

        if (! $hasAnyValue) {
            return DocumentAiExtractionStatus::Failed;
        }

        if ($confidence < $threshold) {
            return DocumentAiExtractionStatus::LowConfidence;
        }

        return $requiresManualReview
            ? DocumentAiExtractionStatus::ManualReview
            : DocumentAiExtractionStatus::Completed;
    }
}
