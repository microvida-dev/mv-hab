<?php

namespace App\Data\Reports;

use App\Enums\ApplicationResultExportMode;
use Carbon\CarbonImmutable;

final readonly class ApplicationResultExportSourceData
{
    /**
     * @param  array<string, mixed>  $sourceReferences
     * @param  list<string>  $warnings
     */
    public function __construct(
        public ApplicationResultExportMode $mode,
        public int $municipalityId,
        public int $contestId,
        public string $municipalityCode,
        public string $contestCode,
        public CarbonImmutable $snapshotAt,
        public bool $official,
        public string $sourceType,
        public array $sourceReferences,
        public ?int $batchId = null,
        public ?int $baseBatchId = null,
        public ?int $targetBatchId = null,
        public ?string $phase = null,
        public ?CarbonImmutable $since = null,
        public array $warnings = [],
    ) {}

    /** @return array<string, mixed> */
    public function fingerprintPayload(): array
    {
        return [
            'schema_version' => '1.0',
            'mode' => $this->mode->value,
            'municipality_code' => $this->municipalityCode,
            'contest_code' => $this->contestCode,
            'snapshot_at' => $this->snapshotAt->toIso8601String(),
            'official' => $this->official,
            'source_type' => $this->sourceType,
            'source_references' => $this->sourceReferences,
            'phase' => $this->phase,
            'since' => $this->since?->toIso8601String(),
        ];
    }
}
