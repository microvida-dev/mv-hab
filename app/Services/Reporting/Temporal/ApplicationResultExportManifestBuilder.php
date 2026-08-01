<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportFileData;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;

final class ApplicationResultExportManifestBuilder
{
    /**
     * @param  list<ApplicationResultExportFileData>  $files
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    public function build(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        array $files,
        bool $documentFilesIncluded,
        array $warnings,
    ): array {
        usort(
            $files,
            static fn (ApplicationResultExportFileData $left, ApplicationResultExportFileData $right): int => strcmp(
                $left->path,
                $right->path,
            ),
        );
        $source = $snapshot->source;

        return [
            'manifest_version' => '1.0',
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
            'base_source' => $this->sourceReference($source->sourceReferences, 'base'),
            'target_source' => $this->sourceReference($source->sourceReferences, 'target'),
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
            'document_files_included' => $documentFilesIncluded,
            'csv_configuration' => [
                'delimiter' => $options->csvDelimiter === "\t"
                    ? 'tab'
                    : $options->csvDelimiter,
                'bom' => $options->csvBom,
            ],
            'generator_version' => 'mvhab-temporal-export-1.0',
            'application_count' => $snapshot->counts['applications'] ?? 0,
            'document_count' => $snapshot->counts['documents'] ?? 0,
            'finding_count' => $snapshot->counts['findings'] ?? 0,
            'change_count' => $snapshot->counts['changes'] ?? 0,
            'files' => array_map(
                static fn (ApplicationResultExportFileData $file): array => $file->toManifestArray(),
                $files,
            ),
            'retention' => [
                'policy' => 'private_temporary',
                'expires_at' => $options->expiresAt->toIso8601String(),
            ],
            'expires_at' => $options->expiresAt->toIso8601String(),
            'warnings' => array_values(array_unique([
                ...$snapshot->warnings,
                ...$warnings,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $references
     * @return array<string, mixed>|null
     */
    private function sourceReference(array $references, string $position): ?array
    {
        foreach (["{$position}_batch", "{$position}_publication"] as $key) {
            $reference = $references[$key] ?? null;
            if (is_array($reference)) {
                return $reference;
            }
        }

        return null;
    }
}
