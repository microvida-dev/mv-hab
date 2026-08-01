<?php

namespace App\Services\Reporting\Temporal\Exporters;

use App\Enums\ApplicationResultExportFormat;

final class ApplicationResultExportWriterRegistry
{
    /** @var array<string, ApplicationResultExportWriter> */
    private array $writers;

    public function __construct(
        ApplicationResultCsvWriter $csv,
        ApplicationResultJsonWriter $json,
        ApplicationResultXmlWriter $xml,
        ApplicationResultXlsxWriter $xlsx,
    ) {
        $this->writers = [
            $csv->format()->value => $csv,
            $json->format()->value => $json,
            $xml->format()->value => $xml,
            $xlsx->format()->value => $xlsx,
        ];
    }

    public function get(ApplicationResultExportFormat $format): ApplicationResultExportWriter
    {
        return $this->writers[$format->value];
    }
}
