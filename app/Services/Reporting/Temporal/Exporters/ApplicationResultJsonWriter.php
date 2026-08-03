<?php

namespace App\Services\Reporting\Temporal\Exporters;

use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Services\Reporting\Temporal\ApplicationResultExportFieldCatalog;
use App\Services\Reporting\Temporal\ApplicationResultExportFileFactory;
use App\Services\Reporting\Temporal\ApplicationResultExportSchemaValidator;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ApplicationResultJsonWriter implements ApplicationResultExportWriter
{
    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportFieldCatalog $catalog,
        private readonly ApplicationResultExportSchemaValidator $validator,
        private readonly CanonicalJsonHasher $hasher,
        private readonly ApplicationResultExportFileFactory $files,
    ) {}

    public function format(): ApplicationResultExportFormat
    {
        return ApplicationResultExportFormat::Json;
    }

    public function write(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
        array $metadata,
    ): array {
        $packagePath = 'applications.json';
        $absolutePath = Storage::disk('local')->path($outputDirectory.'/'.$packagePath);
        $stream = fopen($absolutePath, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Não foi possível criar o JSON temporal.');
        }

        $selected = array_fill_keys(array_map(
            static fn (ApplicationResultExportDataset $dataset): string => $dataset->value,
            $options->datasets,
        ), true);
        $rowCount = 0;

        try {
            $this->validator->validateJsonMetadata($metadata);
            $this->writeChunk($stream, '{"schema_version":"1.0","export":');
            $this->writeChunk($stream, $this->hasher->encode($metadata));

            foreach (ApplicationResultExportDataset::cases() as $dataset) {
                $this->writeChunk($stream, ',"'.$dataset->value.'":[');
                $first = true;
                if (isset($selected[$dataset->value])) {
                    $sourcePath = $snapshot->datasetPaths[$dataset->value] ?? null;
                    if (! is_string($sourcePath)) {
                        throw new RuntimeException("O dataset {$dataset->value} não está disponível.");
                    }

                    $headers = array_map(
                        static fn ($field): string => $field->code,
                        $this->catalog->forDataset(
                            $snapshot->source->mode,
                            $dataset,
                            $options->includeSensitive,
                        ),
                    );
                    foreach ($this->store->rows($sourcePath) as $row) {
                        $ordered = $this->orderedRow($headers, $row);
                        $this->validator->validateJsonRecord(
                            $this->definition($dataset),
                            $ordered,
                        );
                        $this->writeChunk($stream, ($first ? '' : ',').$this->hasher->encode($ordered));
                        $first = false;
                        $rowCount++;
                    }
                }
                $this->writeChunk($stream, ']');
            }

            $this->writeChunk($stream, '}');
        } finally {
            fclose($stream);
        }

        return [$this->files->make(
            $outputDirectory,
            $packagePath,
            $this->format()->mediaType(),
            $rowCount,
            ApplicationResultExportSchemaValidator::JSON_SCHEMA,
        )];
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function orderedRow(array $headers, array $row): array
    {
        $ordered = [];
        foreach ($headers as $header) {
            $ordered[$header] = $row[$header] ?? null;
        }

        return $ordered;
    }

    /** @param resource $stream */
    private function writeChunk($stream, string $chunk): void
    {
        if (fwrite($stream, $chunk) !== strlen($chunk)) {
            throw new RuntimeException('A escrita do JSON temporal ficou incompleta.');
        }
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
