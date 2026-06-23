<?php

namespace App\Data\DocumentIntelligence;

final readonly class DocumentExtractionFlag
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public string $code,
        public string $severity,
        public string $message,
        public ?string $field = null,
        public array $details = [],
    ) {}
}
