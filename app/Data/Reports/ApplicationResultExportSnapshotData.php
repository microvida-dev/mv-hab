<?php

namespace App\Data\Reports;

final readonly class ApplicationResultExportSnapshotData
{
    /**
     * @param  array<string, string>  $datasetPaths
     * @param  array<string, int>  $counts
     * @param  array<string, string>  $checksums
     * @param  list<string>  $warnings
     */
    public function __construct(
        public ApplicationResultExportSourceData $source,
        public array $datasetPaths,
        public array $counts,
        public array $checksums,
        public string $sourceFingerprint,
        public array $warnings = [],
    ) {}
}
