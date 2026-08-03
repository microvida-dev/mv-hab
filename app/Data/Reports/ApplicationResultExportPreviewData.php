<?php

namespace App\Data\Reports;

use Carbon\CarbonImmutable;

final readonly class ApplicationResultExportPreviewData
{
    /**
     * @param  array<string, mixed>  $sourceReferences
     * @param  list<string>  $formats
     * @param  list<string>  $datasets
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $municipalityName,
        public string $contestCode,
        public string $contestTitle,
        public string $mode,
        public string $modeLabel,
        public string $sourceType,
        public bool $official,
        public CarbonImmutable $snapshotAt,
        public array $sourceReferences,
        public array $formats,
        public array $datasets,
        public int $estimatedApplications,
        public bool $sensitiveFieldsIncluded,
        public bool $documentFilesRequested,
        public CarbonImmutable $expiresAt,
        public array $warnings,
    ) {}
}
