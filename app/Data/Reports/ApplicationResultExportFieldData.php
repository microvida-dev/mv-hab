<?php

namespace App\Data\Reports;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationResultExportSensitivity;

final readonly class ApplicationResultExportFieldData
{
    /**
     * @param  list<ApplicationResultExportMode>  $availableInModes
     * @param  list<ApplicationResultExportDataset>  $availableInDatasets
     */
    public function __construct(
        public string $code,
        public string $label,
        public string $type,
        public string $source,
        public ApplicationResultExportSensitivity $sensitivity,
        public bool $nullable,
        public array $availableInModes,
        public array $availableInDatasets,
        public string $schemaVersion = '1.0',
    ) {}

    public function availableFor(
        ApplicationResultExportMode $mode,
        ApplicationResultExportDataset $dataset,
        bool $includeSensitive,
    ): bool {
        if (
            ! in_array($mode, $this->availableInModes, true)
            || ! in_array($dataset, $this->availableInDatasets, true)
        ) {
            return false;
        }

        return $this->sensitivity->includedByDefault()
            || ($includeSensitive
                && $this->sensitivity->canBeIncludedInSensitiveExport());
    }
}
