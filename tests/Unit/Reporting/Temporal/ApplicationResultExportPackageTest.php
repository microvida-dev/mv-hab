<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportPackageOptionsData;
use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Services\Reporting\Temporal\ApplicationResultExportPackageBuilder;
use App\Services\Reporting\Temporal\ApplicationResultExportSchemaValidator;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ApplicationResultExportPackageTest extends TestCase
{
    public function test_package_contains_real_schema_valid_formats_with_content_parity(): void
    {
        Storage::fake('local');
        $snapshot = $this->snapshot();
        $options = $this->packageOptions();

        $package = app(ApplicationResultExportPackageBuilder::class)->build(
            $snapshot,
            $options,
            'report-exports/package-one',
        );

        Storage::disk('local')->assertExists($package->packagePath);
        $this->assertSame(hash_file(
            'sha256',
            Storage::disk('local')->path($package->packagePath),
        ), $package->packageSha256);

        $extractDirectory = Storage::disk('local')->path('report-exports/extracted');
        mkdir($extractDirectory, 0700, true);
        $entries = $this->extract($package->packagePath, $extractDirectory);
        $this->assertSame([
            'applications.csv',
            'applications.json',
            'applications.xlsx',
            'applications.xml',
            'changes.csv',
            'checksums.sha256',
            'documents.csv',
            'findings.csv',
            'manifest.json',
            'schema/mvhab-application-results-v1.schema.json',
            'schema/mvhab-application-results-v1.xsd',
        ], $entries);

        $validator = app(ApplicationResultExportSchemaValidator::class);
        $validator->validateJsonDocument($extractDirectory.'/applications.json');
        $validator->validateXmlDocument($extractDirectory.'/applications.xml');

        $json = json_decode(
            file_get_contents($extractDirectory.'/applications.json') ?: '',
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame('=CAND-0001', $json['applications'][0]['application_number']);
        $this->assertSame('changed', $json['changes'][0]['change_type']);

        $csv = fopen($extractDirectory.'/applications.csv', 'rb');
        $this->assertIsResource($csv);
        $headers = fgetcsv($csv, separator: ';', escape: '');
        $values = fgetcsv($csv, separator: ';', escape: '');
        fclose($csv);
        $this->assertIsArray($headers);
        $this->assertIsArray($values);
        $headers = array_map(static fn (mixed $value): string => (string) $value, $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
        $csvRow = array_combine($headers, $values);
        $this->assertSame("'=CAND-0001", $csvRow['application_number']);

        $xml = new \DOMDocument;
        $this->assertTrue($xml->load($extractDirectory.'/applications.xml', LIBXML_NONET));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('m', 'urn:mvhab:application-results:v1');
        $this->assertSame(
            '=CAND-0001',
            $xpath->evaluate('string(/m:applicationResults/m:applications/m:application/m:application_number)'),
        );

        $reader = new Reader;
        $reader->open($extractDirectory.'/applications.xlsx');
        $sheetNames = [];
        $xlsxApplicationNumber = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetNames[] = $sheet->getName();
            if ($sheet->getName() !== 'Applications') {
                continue;
            }
            $rows = $sheet->getRowIterator();
            $rows->rewind();
            $xlsxHeaders = array_map(
                static function (mixed $value): string {
                    if (! is_string($value)) {
                        throw new RuntimeException('O XLSX contém um cabeçalho inválido.');
                    }

                    return $value;
                },
                $rows->current()->toArray(),
            );
            $rows->next();
            $xlsxValues = $rows->current()->toArray();
            $xlsxRow = array_combine($xlsxHeaders, $xlsxValues);
            $xlsxApplicationNumber = $xlsxRow['application_number'] ?? null;
        }
        $reader->close();
        $this->assertSame(
            ['Applications', 'Documents', 'Findings', 'Changes', 'Manifest'],
            $sheetNames,
        );
        $this->assertSame("'=CAND-0001", $xlsxApplicationNumber);

        $manifest = json_decode(
            file_get_contents($extractDirectory.'/manifest.json') ?: '',
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertSame('1.0', $manifest['manifest_version']);
        $this->assertSame($snapshot->sourceFingerprint, $manifest['source_fingerprint']);
        $this->assertFalse($manifest['document_files_included']);
        $this->assertSame($package->manifestSha256, hash_file(
            'sha256',
            $extractDirectory.'/manifest.json',
        ));
        $this->assertChecksums($extractDirectory);
    }

    public function test_same_snapshot_and_options_produce_reproducible_package_hash(): void
    {
        Storage::fake('local');
        $snapshot = $this->snapshot();
        $options = $this->packageOptions();
        $builder = app(ApplicationResultExportPackageBuilder::class);

        $first = $builder->build($snapshot, $options, 'report-exports/first');
        $second = $builder->build($snapshot, $options, 'report-exports/second');

        $this->assertSame($first->manifestSha256, $second->manifestSha256);
        $this->assertSame($first->packageSha256, $second->packageSha256);
    }

    public function test_document_dossier_fails_closed_without_trusted_security_state(): void
    {
        Storage::fake('local');
        $snapshot = $this->snapshot();
        $base = $this->packageOptions();
        $options = new ApplicationResultExportPackageOptionsData(
            exportPublicId: $base->exportPublicId,
            formats: [ApplicationResultExportFormat::Csv],
            datasets: [
                ApplicationResultExportDataset::Applications,
                ApplicationResultExportDataset::Documents,
            ],
            generatedAt: $base->generatedAt,
            expiresAt: $base->expiresAt,
            includeSensitive: true,
            sensitiveConfirmed: true,
            includeDocumentFiles: true,
        );

        $package = app(ApplicationResultExportPackageBuilder::class)->build(
            $snapshot,
            $options,
            'report-exports/dossier',
        );
        $directory = Storage::disk('local')->path('report-exports/dossier-extracted');
        mkdir($directory, 0700, true);
        $entries = $this->extract($package->packagePath, $directory);

        $this->assertContains('document-index.csv', $entries);
        $this->assertFalse($package->documentFilesIncluded);
        $this->assertFalse(collect($entries)->contains(
            static fn (string $entry): bool => str_starts_with($entry, 'documents/'),
        ));
        $index = file_get_contents($directory.'/document-index.csv');
        $this->assertIsString($index);
        $this->assertStringContainsString('security_state_unavailable', $index);
        $this->assertStringNotContainsString('storage/', $index);
        $this->assertStringNotContainsString('original.pdf', $index);
        $this->assertNotEmpty($package->warnings);
    }

    public function test_csv_configuration_supports_comma_without_bom(): void
    {
        Storage::fake('local');
        $snapshot = $this->snapshot();
        $base = $this->packageOptions();
        $options = new ApplicationResultExportPackageOptionsData(
            exportPublicId: $base->exportPublicId,
            formats: [ApplicationResultExportFormat::Csv],
            datasets: [ApplicationResultExportDataset::Applications],
            generatedAt: $base->generatedAt,
            expiresAt: $base->expiresAt,
            csvDelimiter: ',',
            csvBom: false,
        );

        $package = app(ApplicationResultExportPackageBuilder::class)->build(
            $snapshot,
            $options,
            'report-exports/csv-config',
        );
        $directory = Storage::disk('local')->path('report-exports/csv-config-extracted');
        mkdir($directory, 0700, true);
        $this->extract($package->packagePath, $directory);
        $contents = file_get_contents($directory.'/applications.csv');

        $this->assertIsString($contents);
        $this->assertFalse(str_starts_with($contents, "\xEF\xBB\xBF"));
        $this->assertStringStartsWith('municipality_code,contest_code,', $contents);
    }

    public function test_xml_validator_rejects_doctype(): void
    {
        Storage::fake('local');
        $path = Storage::disk('local')->path('malicious.xml');
        file_put_contents(
            $path,
            '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY ext SYSTEM "file:///etc/passwd">]><x>&ext;</x>',
        );

        $this->expectException(RuntimeException::class);
        app(ApplicationResultExportSchemaValidator::class)->validateXmlDocument($path);
    }

    private function snapshot(): ApplicationResultExportSnapshotData
    {
        $store = app(CanonicalNdjsonStore::class);
        $directory = 'report-exports/source';
        $store->createDirectory($directory);
        $fingerprint = str_repeat('a', 64);
        $paths = [
            'applications' => $directory.'/applications.ndjson',
            'documents' => $directory.'/documents.ndjson',
            'findings' => $directory.'/findings.ndjson',
            'changes' => $directory.'/changes.ndjson',
        ];
        $rows = [
            'applications' => [[
                'municipality_code' => 'ALC',
                'contest_code' => 'CONCURSO-2026',
                'contest_public_id' => 'contest-public',
                'phase_code' => 'revalidation',
                'batch_public_id' => 'batch-target',
                'batch_cycle' => 'revalidation',
                'batch_sequence' => 2,
                'snapshot_at' => '2026-08-01T10:00:00+00:00',
                'published_at' => '2026-08-01T10:00:00+00:00',
                'application_number' => '=CAND-0001',
                'process_number' => 'PROC-0001',
                'submission_status_code' => 'submitted',
                'submission_status_label' => 'Submetida',
                'review_status_code' => 'completed',
                'review_status_label' => 'Concluída',
                'review_result_code' => 'complete_pending_decision',
                'review_result_label' => 'Completa, pendente de decisão',
                'documents_required' => 1,
                'documents_valid' => 1,
                'documents_missing' => 0,
                'documents_invalid' => 0,
                'correction_required' => false,
                'correction_deadline' => null,
                'correction_submitted_at' => null,
                'revalidation_result_code' => 'resolved',
                'eligibility_status_code' => null,
                'eligibility_status_label' => null,
                'score_status_code' => null,
                'score_status_label' => null,
                'final_administrative_status_code' => null,
                'final_administrative_status_label' => null,
                'last_changed_at' => '2026-08-01T09:00:00+00:00',
                'source_fingerprint' => $fingerprint,
            ]],
            'documents' => [[
                'application_number' => '=CAND-0001',
                'process_number' => 'PROC-0001',
                'required_document_code' => 'IDENTIFICATION',
                'document_type_code' => 'IDENTIFICATION',
                'target_type' => 'application',
                'target_reference' => 'PROC-0001',
                'requirement_instance' => 1,
                'required_submissions' => 1,
                'reference_period' => null,
                'document_status_code' => 'validated',
                'version_number' => 1,
                'submitted_at' => '2026-07-31T10:00:00+00:00',
                'validated_at' => '2026-08-01T08:00:00+00:00',
                'source_sha256' => str_repeat('b', 64),
                'carried_forward' => false,
                'source_batch_public_id' => 'batch-target',
            ]],
            'findings' => [[
                'application_number' => '=CAND-0001',
                'finding_code' => 'finding-1',
                'requirement_code' => 'IDENTIFICATION',
                'finding_status_code' => 'resolved',
                'finding_status_label' => 'Resolvido',
                'decision_code' => 'accepted',
                'carried_forward' => false,
                'source_batch_public_id' => 'batch-target',
                'decided_at' => '2026-08-01T08:30:00+00:00',
                'resolved_at' => '2026-08-01T08:30:00+00:00',
            ]],
            'changes' => [[
                'entity_type' => 'document',
                'entity_reference' => '=CAND-0001|IDENTIFICATION|IDENTIFICATION|application|PROC-0001|1|<null>',
                'application_number' => '=CAND-0001',
                'change_type' => 'changed',
                'field_code' => 'document_status_code',
                'before_value' => 'submitted',
                'after_value' => 'validated',
                'before_source' => 'batch-base',
                'after_source' => 'batch-target',
                'changed_at' => '2026-08-01T10:00:00+00:00',
                'sensitive_value_redacted' => false,
            ]],
        ];

        $counts = [];
        $checksums = [];
        foreach ($paths as $dataset => $path) {
            $counts[$dataset] = $store->write($path, $rows[$dataset]);
            $checksums[$dataset] = $store->checksum($path);
        }

        return new ApplicationResultExportSnapshotData(
            source: new ApplicationResultExportSourceData(
                mode: ApplicationResultExportMode::DeltaBetweenBatches,
                municipalityId: 1,
                contestId: 1,
                municipalityCode: 'ALC',
                contestCode: 'CONCURSO-2026',
                snapshotAt: CarbonImmutable::parse('2026-08-01 10:00:00', 'UTC'),
                official: false,
                sourceType: 'sealed_batch_delta',
                sourceReferences: [
                    'base_batch' => ['public_id' => 'batch-base'],
                    'target_batch' => ['public_id' => 'batch-target'],
                ],
            ),
            datasetPaths: $paths,
            counts: $counts,
            checksums: $checksums,
            sourceFingerprint: $fingerprint,
        );
    }

    private function packageOptions(): ApplicationResultExportPackageOptionsData
    {
        return new ApplicationResultExportPackageOptionsData(
            exportPublicId: '0198dc22-5d88-72d3-9ba2-85d09011d7bd',
            formats: ApplicationResultExportFormat::cases(),
            datasets: ApplicationResultExportDataset::cases(),
            generatedAt: CarbonImmutable::parse('2026-08-01 10:15:00', 'UTC'),
            expiresAt: CarbonImmutable::parse('2026-08-08 10:15:00', 'UTC'),
        );
    }

    /** @return list<string> */
    private function extract(string $packagePath, string $directory): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($packagePath)));
        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $this->assertIsString($name);
            $entries[] = $name;
        }
        $this->assertTrue($zip->extractTo($directory));
        $zip->close();
        sort($entries, SORT_STRING);

        return $entries;
    }

    private function assertChecksums(string $directory): void
    {
        $contents = file($directory.'/checksums.sha256', FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($contents);
        $paths = [];
        foreach ($contents as $line) {
            [$sha256, $path] = explode('  ', $line, 2);
            $paths[] = $path;
            $this->assertSame($sha256, hash_file('sha256', $directory.'/'.$path));
        }
        $sorted = $paths;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $paths);
        $this->assertNotContains('checksums.sha256', $paths);
    }
}
