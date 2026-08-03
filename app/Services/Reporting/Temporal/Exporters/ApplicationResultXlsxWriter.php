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
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;
use ZipArchive;

final class ApplicationResultXlsxWriter implements ApplicationResultExportWriter
{
    private const MAX_DATA_ROWS_PER_SHEET = 1_048_575;

    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportFieldCatalog $catalog,
        private readonly SpreadsheetCellSanitizer $cells,
        private readonly CanonicalJsonHasher $hasher,
        private readonly ApplicationResultExportFileFactory $files,
    ) {}

    public function format(): ApplicationResultExportFormat
    {
        return ApplicationResultExportFormat::Xlsx;
    }

    public function write(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
        array $metadata,
    ): array {
        $packagePath = 'applications.xlsx';
        $absolutePath = Storage::disk('local')->path($outputDirectory.'/'.$packagePath);
        $writer = new Writer;
        $writer->setCreator('MV HAB');
        $rowCount = 0;

        try {
            $writer->openToFile($absolutePath);
            $firstSheet = true;
            foreach ($options->datasets as $dataset) {
                $sourcePath = $snapshot->datasetPaths[$dataset->value] ?? null;
                if (! is_string($sourcePath)) {
                    throw new RuntimeException("O dataset {$dataset->value} não está disponível.");
                }

                $fields = $this->catalog->forDataset(
                    $snapshot->source->mode,
                    $dataset,
                    $options->includeSensitive,
                );
                $headers = array_map(static fn ($field): string => $field->code, $fields);
                $sheetNumber = 1;
                $sheet = $firstSheet
                    ? $writer->getCurrentSheet()
                    : $writer->addNewSheetAndMakeItCurrent();
                $firstSheet = false;
                $this->prepareSheet($sheet, $this->sheetName($dataset, $sheetNumber), $headers, $writer);
                $rowsOnSheet = 0;

                foreach ($this->store->rows($sourcePath) as $row) {
                    if ($rowsOnSheet === self::MAX_DATA_ROWS_PER_SHEET) {
                        $this->finishSheet($sheet, count($headers), $rowsOnSheet);
                        $sheetNumber++;
                        $sheet = $writer->addNewSheetAndMakeItCurrent();
                        $this->prepareSheet(
                            $sheet,
                            $this->sheetName($dataset, $sheetNumber),
                            $headers,
                            $writer,
                        );
                        $rowsOnSheet = 0;
                    }

                    $values = [];
                    foreach ($headers as $header) {
                        $values[] = $this->cells->value($row[$header] ?? null);
                    }
                    $writer->addRow(Row::fromValues($values));
                    $rowsOnSheet++;
                    $rowCount++;
                }
                $this->finishSheet($sheet, count($headers), $rowsOnSheet);
            }

            $manifestSheet = $writer->addNewSheetAndMakeItCurrent();
            $manifestSheet->setName('Manifest');
            $manifestSheet->setSheetView((new SheetView)->setFreezeRow(2));
            $writer->addRow(Row::fromValues(['Campo', 'Valor'], $this->headerStyle()));
            $manifestRows = 0;
            foreach ($metadata as $field => $value) {
                $writer->addRow(Row::fromValues([
                    $field,
                    $this->cells->value(
                        is_array($value) || is_object($value)
                            ? $this->hasher->encode($value)
                            : $value,
                    ),
                ]));
                $manifestRows++;
            }
            $manifestSheet->setAutoFilter(new AutoFilter(0, 1, 1, $manifestRows + 1));
            $writer->close();
            $this->normalizeWorkbook(
                $absolutePath,
                $snapshot->source->snapshotAt->getTimestamp(),
                $options->generatedAt->toIso8601ZuluString(),
            );
        } catch (Throwable $exception) {
            $writer->close();
            @unlink($absolutePath);

            throw $exception;
        }

        return [$this->files->make(
            $outputDirectory,
            $packagePath,
            $this->format()->mediaType(),
            $rowCount,
            'mvhab-application-results-v1',
        )];
    }

    /** @param list<string> $headers */
    private function prepareSheet(
        Sheet $sheet,
        string $name,
        array $headers,
        Writer $writer,
    ): void {
        $sheet->setName($name);
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));
        $writer->addRow(Row::fromValues($headers, $this->headerStyle()));
    }

    private function finishSheet(Sheet $sheet, int $columnCount, int $rowCount): void
    {
        if ($columnCount > 0) {
            $lastRow = $rowCount + 1;
            if ($lastRow < 1) {
                throw new RuntimeException('A folha XLSX possui uma contagem de linhas inválida.');
            }
            $sheet->setAutoFilter(new AutoFilter(0, 1, $columnCount - 1, $lastRow));
        }
    }

    private function sheetName(ApplicationResultExportDataset $dataset, int $number): string
    {
        $base = match ($dataset) {
            ApplicationResultExportDataset::Applications => 'Applications',
            ApplicationResultExportDataset::Documents => 'Documents',
            ApplicationResultExportDataset::Findings => 'Findings',
            ApplicationResultExportDataset::Changes => 'Changes',
        };

        return $number === 1 ? $base : $base.' '.$number;
    }

    private function headerStyle(): Style
    {
        return (new Style)->setFontBold();
    }

    private function normalizeWorkbook(
        string $absolutePath,
        int $timestamp,
        string $generatedAt,
    ): void {
        $zip = new ZipArchive;
        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('O XLSX gerado não pode ser validado.');
        }

        try {
            $core = $zip->getFromName('docProps/core.xml');
            if (! is_string($core)) {
                throw new RuntimeException('O XLSX não contém metadata válida.');
            }
            $normalized = preg_replace(
                '/(<dcterms:(?:created|modified)[^>]*>).*?(<\/dcterms:(?:created|modified)>)/',
                '$1'.$generatedAt.'$2',
                $core,
            );
            if (! is_string($normalized) || ! $zip->addFromString('docProps/core.xml', $normalized)) {
                throw new RuntimeException('Não foi possível normalizar a metadata do XLSX.');
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if (! is_string($name)) {
                    throw new RuntimeException('O XLSX contém uma entrada inválida.');
                }
                if (
                    str_starts_with($name, 'xl/externalLinks/')
                    || str_ends_with(strtolower($name), 'vbaproject.bin')
                ) {
                    throw new RuntimeException('O XLSX contém conteúdo externo ou macros.');
                }
                $zip->setMtimeIndex($index, max($timestamp, 315532800));
            }
        } finally {
            $zip->close();
        }
    }
}
