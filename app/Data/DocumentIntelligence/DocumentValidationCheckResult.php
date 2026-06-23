<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiValidationSeverity;
use App\Enums\DocumentAiValidationStatus;

final readonly class DocumentValidationCheckResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public DocumentValidationRule $rule,
        public DocumentAiValidationStatus $status,
        public DocumentAiValidationSeverity $severity,
        public float $confidence,
        public mixed $candidateValue,
        public mixed $extractedValue,
        public string $message,
        public ?string $recommendation,
        public bool $requiresManualReview,
        public array $metadata = [],
    ) {}
}
