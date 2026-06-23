<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionSource;

final readonly class ExtractedDocumentField
{
    public function __construct(
        public string $key,
        public string $label,
        public DocumentAiExtractedFieldType $type,
        public string|int|float|bool|null $value,
        public string|int|float|bool|null $normalizedValue,
        public float $confidence,
        public DocumentAiExtractionSource $source,
        public bool $requiresReview,
        public bool $sensitive = false,
        public bool $healthData = false,
        public ?int $page = null,
    ) {}
}
