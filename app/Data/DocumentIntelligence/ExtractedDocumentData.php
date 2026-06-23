<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;

final readonly class ExtractedDocumentData
{
    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, array<string, mixed>>  $fieldMetadata
     */
    public function __construct(
        public int $analysisId,
        public ?DocumentAiDocumentType $documentType,
        public array $fields,
        public array $fieldMetadata,
    ) {}

    public function value(string $path): mixed
    {
        $key = str_starts_with($path, 'fields.')
            ? substr($path, 7)
            : $path;

        return $this->fields[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(string $fieldKey): array
    {
        return $this->fieldMetadata[$fieldKey] ?? [];
    }
}
