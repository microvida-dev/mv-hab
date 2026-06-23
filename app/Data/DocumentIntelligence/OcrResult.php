<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiOcrStatus;

final readonly class OcrResult
{
    /**
     * @param  list<string>  $signals
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public DocumentAiOcrStatus $status,
        public bool $available,
        public string $method,
        public ?string $text,
        public float $qualityScore,
        public ?int $pagesCount,
        public int $durationMs,
        public array $signals = [],
        public array $metadata = [],
        public ?string $failureCode = null,
    ) {}
}
