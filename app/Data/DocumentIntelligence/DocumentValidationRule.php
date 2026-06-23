<?php

namespace App\Data\DocumentIntelligence;

use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;

final readonly class DocumentValidationRule
{
    public function __construct(
        public DocumentAiValidationGroup $group,
        public string $key,
        public string $label,
        public string $candidatePath,
        public string $extractedPath,
        public DocumentAiComparisonMethod $method,
        public string $valueType,
        public DocumentAiValidationSeverity $baseSeverity,
        public bool $sensitive = true,
        public bool $income = false,
        public bool $healthData = false,
        public ?string $message = null,
        public ?string $recommendation = null,
    ) {}

    public function extractedFieldKey(): string
    {
        return str_starts_with($this->extractedPath, 'fields.')
            ? substr($this->extractedPath, 7)
            : $this->extractedPath;
    }
}
