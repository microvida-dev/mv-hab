<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;

final readonly class KeywordClassificationResult
{
    /**
     * @param  list<string>  $signals
     * @param  array<string, int>  $scores
     */
    public function __construct(
        public DocumentAiDocumentType $documentType,
        public float $confidence,
        public array $signals,
        public array $scores,
    ) {}
}
