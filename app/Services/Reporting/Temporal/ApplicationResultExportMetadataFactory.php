<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;

final class ApplicationResultExportMetadataFactory
{
    /** @return array<string, mixed> */
    public function build(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
    ): array {
        $source = $snapshot->source;

        return [
            'schema_version' => '1.0',
            'export_public_id' => $options->exportPublicId,
            'generated_at' => $options->generatedAt->toIso8601String(),
            'snapshot_at' => $source->snapshotAt->toIso8601String(),
            'municipality_code' => $source->municipalityCode,
            'contest_code' => $source->contestCode,
            'mode' => $source->mode->value,
            'official' => $source->official,
            'source_type' => $source->sourceType,
            'source_references' => $source->sourceReferences,
            'source_fingerprint' => $snapshot->sourceFingerprint,
            'formats' => array_map(
                static fn (ApplicationResultExportFormat $format): string => $format->value,
                $options->formats,
            ),
            'datasets' => array_map(
                static fn (ApplicationResultExportDataset $dataset): string => $dataset->value,
                $options->datasets,
            ),
            'sensitive_fields_included' => $options->includeSensitive,
            'document_files_requested' => $options->includeDocumentFiles,
            'csv_configuration' => [
                'delimiter' => $options->csvDelimiter === "\t"
                    ? 'tab'
                    : $options->csvDelimiter,
                'bom' => $options->csvBom,
            ],
            'counts' => [
                'applications' => $snapshot->counts['applications'] ?? 0,
                'documents' => $snapshot->counts['documents'] ?? 0,
                'findings' => $snapshot->counts['findings'] ?? 0,
                'changes' => $snapshot->counts['changes'] ?? 0,
            ],
        ];
    }
}
