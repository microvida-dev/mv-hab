<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;

class DocumentAiRiskFlag
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly DocumentAiRiskFlagCode $code,
        public readonly DocumentAiRiskSeverity $severity,
        public readonly int $scoreImpact,
        public readonly string $message,
        public readonly string $detectedBy,
        public readonly float $confidence,
        public readonly bool $requiresManualReview = true,
        public readonly ?string $suggestionTemplate = null,
        public readonly array $metadata = [],
    ) {}
}
