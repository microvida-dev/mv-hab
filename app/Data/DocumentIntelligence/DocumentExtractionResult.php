<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractionStatus;

final readonly class DocumentExtractionResult
{
    /**
     * @param  list<ExtractedDocumentField>  $fields
     * @param  list<DocumentExtractionFlag>  $flags
     */
    public function __construct(
        public string $schemaVersion,
        public DocumentAiDocumentType $documentType,
        public DocumentAiExtractionStatus $status,
        public float $confidence,
        public array $fields,
        public array $flags,
        public bool $requiresManualReview,
        public string $source,
    ) {}
}
