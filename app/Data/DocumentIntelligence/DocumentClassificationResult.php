<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;

final readonly class DocumentClassificationResult
{
    /**
     * @param  list<string>  $signals
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public DocumentAiDocumentType $documentType,
        public string $label,
        public float $confidence,
        public string $source,
        public array $signals,
        public bool $requiresManualReview,
        public DocumentAiClassificationStatus $status,
        public array $raw,
    ) {}
}
