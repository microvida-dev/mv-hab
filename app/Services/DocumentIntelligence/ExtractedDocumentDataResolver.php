<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\ExtractedDocumentData;
use App\Models\DocumentAiAnalysis;

class ExtractedDocumentDataResolver
{
    public function resolve(DocumentAiAnalysis $analysis): ExtractedDocumentData
    {
        $analysis->loadMissing(['fields']);

        $fields = [];
        $metadata = [];

        foreach ($analysis->fields as $field) {
            $fieldMetadata = is_array($field->metadata) ? $field->metadata : [];
            if (($fieldMetadata['category'] ?? null) !== 'structured_extraction') {
                continue;
            }

            $fields[$field->key] = $field->normalized_value ?? $field->value;
            $metadata[$field->key] = [
                'label' => $field->label,
                'confidence' => $field->confidence !== null ? (float) $field->confidence : null,
                'sensitive' => (bool) ($fieldMetadata['sensitive'] ?? true),
                'income' => (bool) ($fieldMetadata['income'] ?? false),
                'health_data' => (bool) ($fieldMetadata['health_data'] ?? false),
                'source' => $field->source,
                'requires_review' => (bool) $field->requires_review,
            ];
        }

        return new ExtractedDocumentData(
            analysisId: (int) $analysis->id,
            documentType: $analysis->detected_document_type,
            fields: $fields,
            fieldMetadata: $metadata,
        );
    }
}
