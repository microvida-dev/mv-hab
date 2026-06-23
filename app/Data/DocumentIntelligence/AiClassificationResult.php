<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;

final readonly class AiClassificationResult
{
    /**
     * @param  list<string>  $signals
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public ?DocumentAiDocumentType $documentType,
        public ?string $label,
        public float $confidence,
        public array $signals,
        public bool $requiresManualReview,
        public string $source,
        public ?string $reason = null,
        public ?array $rawResponse = null,
        public ?string $failureCode = null,
    ) {}
}
