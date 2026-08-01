<?php

namespace App\Services\Reporting\Temporal\Exporters;

use App\Data\Reports\ApplicationResultExportFileData;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportFormat;

interface ApplicationResultExportWriter
{
    public function format(): ApplicationResultExportFormat;

    /**
     * @param  array<string, mixed>  $metadata
     * @return list<ApplicationResultExportFileData>
     */
    public function write(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
        array $metadata,
    ): array;
}
