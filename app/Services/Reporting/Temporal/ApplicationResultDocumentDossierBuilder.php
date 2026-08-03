<?php

namespace App\Services\Reporting\Temporal;

use App\Data\Reports\ApplicationResultDocumentDossierData;
use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Enums\ApplicationResultExportDataset;
use App\Services\Support\CanonicalJsonHasher;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ApplicationResultDocumentDossierBuilder
{
    private const HEADERS = [
        'application_number',
        'document_public_id',
        'document_type_code',
        'requirement_code',
        'version_number',
        'reference_period',
        'status_code',
        'package_path',
        'mime_type',
        'size',
        'sha256',
        'included',
        'exclusion_reason',
    ];

    public function __construct(
        private readonly CanonicalNdjsonStore $store,
        private readonly SpreadsheetCellSanitizer $cells,
        private readonly CanonicalJsonHasher $hasher,
        private readonly ApplicationResultExportFileFactory $files,
    ) {}

    public function build(
        ApplicationResultExportSnapshotData $snapshot,
        ApplicationResultExportPackageOptionsData $options,
        string $outputDirectory,
    ): ApplicationResultDocumentDossierData {
        if (! $options->includeDocumentFiles) {
            return new ApplicationResultDocumentDossierData(false, [], []);
        }

        if (! in_array(ApplicationResultExportDataset::Documents, $options->datasets, true)) {
            throw new RuntimeException('O dossier exige o dataset documental.');
        }

        $documentPath = $snapshot->datasetPaths['documents'] ?? null;
        if (! is_string($documentPath)) {
            throw new RuntimeException('O snapshot documental não está disponível.');
        }

        $changedKeys = $options->changedDocumentsOnly
            ? $this->changedDocumentKeys($snapshot)
            : null;
        $packagePath = 'document-index.csv';
        $absolutePath = Storage::disk('local')->path($outputDirectory.'/'.$packagePath);
        $stream = fopen($absolutePath, 'wb');
        if ($stream === false) {
            throw new RuntimeException('Não foi possível criar o índice documental.');
        }

        $count = 0;
        try {
            if ($options->csvBom && fwrite($stream, "\xEF\xBB\xBF") !== 3) {
                throw new RuntimeException('Não foi possível escrever o BOM do índice documental.');
            }
            if (fputcsv($stream, self::HEADERS, $options->csvDelimiter, '"', '') === false) {
                throw new RuntimeException('Não foi possível escrever o cabeçalho documental.');
            }

            foreach ($this->store->rows($documentPath) as $document) {
                if (
                    is_array($changedKeys)
                    && ! isset($changedKeys[$this->documentKey($document)])
                ) {
                    continue;
                }

                $indexRow = [
                    'application_number' => $document['application_number'] ?? null,
                    'document_public_id' => $this->opaqueDocumentId($snapshot, $document),
                    'document_type_code' => $document['document_type_code'] ?? null,
                    'requirement_code' => $document['required_document_code'] ?? null,
                    'version_number' => $document['version_number'] ?? null,
                    'reference_period' => $document['reference_period'] ?? null,
                    'status_code' => $document['document_status_code'] ?? null,
                    'package_path' => null,
                    'mime_type' => null,
                    'size' => null,
                    'sha256' => $document['source_sha256'] ?? null,
                    'included' => false,
                    'exclusion_reason' => 'security_state_unavailable',
                ];
                $values = array_map(
                    fn (string $header): string => $this->cells->value($indexRow[$header] ?? null),
                    self::HEADERS,
                );
                if (fputcsv($stream, $values, $options->csvDelimiter, '"', '') === false) {
                    throw new RuntimeException('Não foi possível escrever o índice documental.');
                }
                $count++;
            }
        } finally {
            fclose($stream);
        }

        return new ApplicationResultDocumentDossierData(
            documentFilesIncluded: false,
            files: [$this->files->make(
                $outputDirectory,
                $packagePath,
                'text/csv; charset=UTF-8',
                $count,
                'mvhab-document-index-v1',
            )],
            warnings: [
                'Os binários documentais foram excluídos: o repositório não possui um estado confiável de antivírus/quarentena.',
            ],
        );
    }

    /** @return array<string, true> */
    private function changedDocumentKeys(ApplicationResultExportSnapshotData $snapshot): array
    {
        $changesPath = $snapshot->datasetPaths['changes'] ?? null;
        if (! is_string($changesPath)) {
            throw new RuntimeException('O delta documental não está disponível.');
        }

        $keys = [];
        foreach ($this->store->rows($changesPath) as $change) {
            if (($change['entity_type'] ?? null) !== 'document') {
                continue;
            }
            $reference = $change['entity_reference'] ?? null;
            if (is_string($reference) && $reference !== '') {
                $keys[$reference] = true;
            }
        }

        return $keys;
    }

    /** @param array<string, mixed> $document */
    private function documentKey(array $document): string
    {
        $parts = [];
        foreach ([
            'application_number',
            'required_document_code',
            'document_type_code',
            'target_type',
            'target_reference',
            'requirement_instance',
            'reference_period',
        ] as $field) {
            $value = $document[$field] ?? null;
            $parts[] = $value === null
                ? '<null>'
                : str_replace(['\\', '|'], ['\\\\', '\\|'], (string) $value);
        }

        return implode('|', $parts);
    }

    /** @param array<string, mixed> $document */
    private function opaqueDocumentId(
        ApplicationResultExportSnapshotData $snapshot,
        array $document,
    ): string {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new RuntimeException('A chave da aplicação é necessária para pseudonimizar documentos.');
        }

        $payload = $this->hasher->encode([
            'source_fingerprint' => $snapshot->sourceFingerprint,
            'document_key' => $this->documentKey($document),
            'version_number' => $document['version_number'] ?? null,
            'source_sha256' => $document['source_sha256'] ?? null,
        ]);

        return 'doc-'.substr(hash_hmac('sha256', $payload, $key), 0, 32);
    }
}
