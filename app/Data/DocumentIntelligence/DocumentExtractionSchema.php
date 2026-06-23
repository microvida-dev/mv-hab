<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;

final readonly class DocumentExtractionSchema
{
    /**
     * @param  array<string, array{type: DocumentAiExtractedFieldType, label: string, sensitive: bool, health_data: bool, required: bool}>  $fields
     */
    public function __construct(
        public DocumentAiDocumentType $documentType,
        public string $label,
        public array $fields,
    ) {}

    public function hasField(string $key): bool
    {
        return array_key_exists($key, $this->fields);
    }
}
