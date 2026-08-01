<?php

namespace App\Services\Reporting\Temporal\Exporters;

use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Services\Reporting\Temporal\ApplicationResultExportFieldCatalog;
use App\Services\Reporting\Temporal\ApplicationResultExportFileFactory;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use App\Services\Reporting\Temporal\SpreadsheetCellSanitizer;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ApplicationResultCsvWriter implements ApplicationResultExportWriter
{
    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportFieldCatalog $catalog,
        private readonly SpreadsheetCellSanitizer $cells,
        private readonly ApplicationResultExportFileFactory $files,
    ) {}

    public function format(): ApplicationResultExportFormat
    {
        return ApplicationResultExportFormat::Csv;
    }

    public function write(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
        array $metadata,
    ): array {
        $files = [];
        foreach ($options->datasets as $dataset) {
            $sourcePath = $snapshot->datasetPaths[$dataset->value] ?? null;
            if (! is_string($sourcePath)) {
                throw new RuntimeException("O dataset {$dataset->value} não está disponível.");
            }

            $packagePath = $dataset->value.'.csv';
            $absolutePath = Storage::disk('local')->path($outputDirectory.'/'.$packagePath);
            $stream = fopen($absolutePath, 'wb');
            if ($stream === false) {
                throw new RuntimeException('Não foi possível criar o CSV temporal.');
            }

            $fields = $this->catalog->forDataset(
                $snapshot->source->mode,
                $dataset,
                $options->includeSensitive,
            );
            $headers = array_map(
                static fn ($field): string => $field->code,
                $fields,
            );
            $rowCount = 0;

            try {
                if ($options->csvBom && fwrite($stream, "\xEF\xBB\xBF") !== 3) {
                    throw new RuntimeException('Não foi possível escrever o BOM do CSV.');
                }
                if (fputcsv($stream, $headers, $options->csvDelimiter, '"', '') === false) {
                    throw new RuntimeException('Não foi possível escrever o cabeçalho CSV.');
                }

                foreach ($this->store->rows($sourcePath) as $row) {
                    $values = [];
                    foreach ($headers as $header) {
                        $values[] = $this->cells->value($row[$header] ?? null);
                    }
                    if (fputcsv($stream, $values, $options->csvDelimiter, '"', '') === false) {
                        throw new RuntimeException('Não foi possível escrever uma linha CSV.');
                    }
                    $rowCount++;
                }
            } finally {
                fclose($stream);
            }

            $files[] = $this->files->make(
                $outputDirectory,
                $packagePath,
                $this->format()->mediaType(),
                $rowCount,
                'mvhab-application-results-v1#/$defs/'.$this->definition($dataset),
            );
        }

        return $files;
    }

    private function definition(ApplicationResultExportDataset $dataset): string
    {
        return match ($dataset) {
            ApplicationResultExportDataset::Applications => 'application',
            ApplicationResultExportDataset::Documents => 'document',
            ApplicationResultExportDataset::Findings => 'finding',
            ApplicationResultExportDataset::Changes => 'change',
        };
    }
}
