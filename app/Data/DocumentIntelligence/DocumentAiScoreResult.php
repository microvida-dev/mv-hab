<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiScoreLabel;

class DocumentAiScoreResult
{
    /**
     * @param  array<string, int>  $components
     * @param  array<string, mixed>  $explanation
     */
    public function __construct(
        public readonly int $score,
        public readonly DocumentAiScoreLabel $label,
        public readonly array $components,
        public readonly string $summary,
        public readonly array $explanation,
        public readonly bool $requiresManualReview,
    ) {}
}
