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
use XMLWriter;

final class ApplicationResultXmlWriter implements ApplicationResultExportWriter
{
    private const NAMESPACE = 'urn:mvhab:application-results:v1';

    private const XSI_NAMESPACE = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly ApplicationResultExportFieldCatalog $catalog,
        private readonly ApplicationResultExportSchemaValidator $validator,
        private readonly CanonicalJsonHasher $hasher,
        private readonly ApplicationResultExportFileFactory $files,
    ) {}

    public function format(): ApplicationResultExportFormat
    {
        return ApplicationResultExportFormat::Xml;
    }

    public function write(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
        array $metadata,
    ): array {
        $packagePath = 'applications.xml';
        $absolutePath = Storage::disk('local')->path($outputDirectory.'/'.$packagePath);
        $writer = new XMLWriter;
        if (! $writer->openURI($absolutePath)) {
            throw new RuntimeException('Não foi possível criar o XML temporal.');
        }

        $selected = array_fill_keys(array_map(
            static fn (ApplicationResultExportDataset $dataset): string => $dataset->value,
            $options->datasets,
        ), true);
        $rowCount = 0;

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElementNS(null, 'applicationResults', self::NAMESPACE);
        $writer->writeAttributeNS('xmlns', 'xsi', null, self::XSI_NAMESPACE);
        $writer->writeAttribute('schemaVersion', '1.0');
        $this->writeMetadata($writer, $metadata);

        foreach (ApplicationResultExportDataset::cases() as $dataset) {
            $writer->startElement($dataset->value);
            if (isset($selected[$dataset->value])) {
                $sourcePath = $snapshot->datasetPaths[$dataset->value] ?? null;
                if (! is_string($sourcePath)) {
                    throw new RuntimeException("O dataset {$dataset->value} não está disponível.");
                }
                $fields = $this->catalog->forDataset(
                    $snapshot->source->mode,
                    $dataset,
                    $options->includeSensitive,
                );

                foreach ($this->store->rows($sourcePath) as $row) {
                    $writer->startElement($this->recordElement($dataset));
                    foreach ($fields as $field) {
                        $this->writeValue($writer, $field->code, $row[$field->code] ?? null);
                    }
                    $writer->endElement();
                    $rowCount++;
                }
            }
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
        if (! is_file($absolutePath) || filesize($absolutePath) === 0) {
            throw new RuntimeException('A escrita do XML temporal ficou incompleta.');
        }

        $this->validator->validateXmlDocument($absolutePath);

        return [$this->files->make(
            $outputDirectory,
            $packagePath,
            $this->format()->mediaType(),
            $rowCount,
            ApplicationResultExportSchemaValidator::XML_SCHEMA,
        )];
    }

    /** @param array<string, mixed> $metadata */
    private function writeMetadata(XMLWriter $writer, array $metadata): void
    {
        $writer->startElement('export');
        foreach ([
            'schema_version',
            'export_public_id',
            'generated_at',
            'snapshot_at',
            'municipality_code',
            'contest_code',
            'mode',
            'official',
            'source_type',
            'source_references',
            'source_fingerprint',
            'formats',
            'datasets',
            'sensitive_fields_included',
            'document_files_requested',
            'csv_configuration',
            'counts',
        ] as $field) {
            $this->writeValue($writer, $field, $metadata[$field] ?? null);
        }
        $writer->endElement();
    }

    private function writeValue(XMLWriter $writer, string $name, mixed $value): void
    {
        $writer->startElement($name);
        if ($value === null) {
            $writer->writeAttributeNS('xsi', 'nil', self::XSI_NAMESPACE, 'true');
        } else {
            $writer->text($this->stringValue($value));
        }
        $writer->endElement();
    }

    private function stringValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return $this->hasher->encode($value);
        }

        return (string) $value;
    }

    private function recordElement(ApplicationResultExportDataset $dataset): string
    {
        return match ($dataset) {
            ApplicationResultExportDataset::Applications => 'application',
            ApplicationResultExportDataset::Documents => 'document',
            ApplicationResultExportDataset::Findings => 'finding',
            ApplicationResultExportDataset::Changes => 'change',
        };
    }
}
