<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiRiskFlagCode;
use App\Enums\DocumentAiRiskSeverity;
use App\Enums\DocumentAiSuggestionStatus;

class DocumentAiSuggestionDraft
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly DocumentAiRiskFlagCode $flagCode,
        public readonly DocumentAiRiskSeverity $severity,
        public readonly string $suggestion,
        public readonly DocumentAiSuggestionStatus $status,
        public readonly array $metadata = [],
    ) {}
}
