<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;

class DocumentExtractionSchemaRegistry
{
    public function schemaFor(DocumentAiDocumentType $documentType): ?DocumentExtractionSchema
    {
        $configuredSchemas = config('document-ai-extraction.schemas', []);
        if (! is_array($configuredSchemas)) {
            return null;
        }

        /** @var array<string, mixed> $schemas */
        $schemas = $configuredSchemas;
        $definition = $schemas[$documentType->value] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        $fields = [];

        $configuredFields = $definition['fields'] ?? [];
        if (! is_array($configuredFields)) {
            return null;
        }

        foreach ($configuredFields as $key => $field) {
            if (! is_string($key) || ! is_array($field)) {
                continue;
            }

            $type = $field['type'] ?? DocumentAiExtractedFieldType::Unknown;
            if (is_string($type)) {
                $type = DocumentAiExtractedFieldType::tryFrom($type) ?? DocumentAiExtractedFieldType::Unknown;
            }

            if (! $type instanceof DocumentAiExtractedFieldType) {
                $type = DocumentAiExtractedFieldType::Unknown;
            }

            $fields[$key] = [
                'type' => $type,
                'label' => is_string($field['label'] ?? null) ? $field['label'] : $key,
                'sensitive' => (bool) ($field['sensitive'] ?? true),
                'health_data' => (bool) ($field['health_data'] ?? false),
                'required' => (bool) ($field['required'] ?? false),
            ];
        }

        if ($fields === []) {
            return null;
        }

        return new DocumentExtractionSchema(
            documentType: $documentType,
            label: is_string($definition['label'] ?? null) ? $definition['label'] : $documentType->label(),
            fields: $fields,
        );
    }

    /**
     * @return array<string, DocumentExtractionSchema>
     */
    public function supportedSchemas(): array
    {
        $schemas = [];

        foreach (DocumentAiDocumentType::cases() as $type) {
            $schema = $this->schemaFor($type);

            if ($schema instanceof DocumentExtractionSchema) {
                $schemas[$type->value] = $schema;
            }
        }

        return $schemas;
    }
}
