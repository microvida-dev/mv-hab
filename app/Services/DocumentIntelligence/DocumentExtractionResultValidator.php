<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionSource;

class DocumentExtractionResultValidator
{
    /**
     * @param  list<ExtractedDocumentField>  $fields
     * @param  list<DocumentExtractionFlag>  $flags
     * @return array{fields: list<ExtractedDocumentField>, flags: list<DocumentExtractionFlag>}
     */
    public function validate(DocumentExtractionSchema $schema, array $fields, array $flags): array
    {
        $validated = [];
        $knownFields = [];

        foreach ($fields as $field) {
            if (! $schema->hasField($field->key)) {
                continue;
            }

            $knownFields[$field->key] = $this->withSchemaDefinition($schema, $field);
        }

        foreach ($schema->fields as $key => $definition) {
            if (isset($knownFields[$key])) {
                $validated[] = $knownFields[$key];

                continue;
            }

            $required = (bool) $definition['required'];

            if ($required) {
                $flags[] = new DocumentExtractionFlag(
                    code: 'missing_required_field',
                    severity: 'medium',
                    message: 'Campo obrigatório não extraído.',
                    field: $key,
                );
            }

            $validated[] = new ExtractedDocumentField(
                key: $key,
                label: $definition['label'],
                type: $definition['type'],
                value: null,
                normalizedValue: null,
                confidence: 0.0,
                source: DocumentAiExtractionSource::Combined,
                requiresReview: $required,
                sensitive: (bool) $definition['sensitive'],
                healthData: (bool) $definition['health_data'],
            );
        }

        return [
            'fields' => $validated,
            'flags' => $this->uniqueFlags($flags),
        ];
    }

    private function withSchemaDefinition(DocumentExtractionSchema $schema, ExtractedDocumentField $field): ExtractedDocumentField
    {
        $definition = $schema->fields[$field->key];

        return new ExtractedDocumentField(
            key: $field->key,
            label: $definition['label'],
            type: $definition['type'],
            value: $field->value,
            normalizedValue: $field->normalizedValue,
            confidence: round(max(0.0, min(1.0, $field->confidence)), 2),
            source: $field->source,
            requiresReview: $field->requiresReview,
            sensitive: (bool) $definition['sensitive'],
            healthData: (bool) $definition['health_data'],
            page: $field->page,
        );
    }

    /**
     * @param  list<DocumentExtractionFlag>  $flags
     * @return list<DocumentExtractionFlag>
     */
    private function uniqueFlags(array $flags): array
    {
        $unique = [];
        $seen = [];

        foreach ($flags as $flag) {
            $key = $flag->code.'|'.$flag->field.'|'.$flag->severity;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $flag;
        }

        return $unique;
    }
}
