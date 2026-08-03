<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Data\Reports\ApplicationResultExportSnapshotData;
use App\Data\Reports\ApplicationResultExportSourceData;
use App\Enums\ApplicationResultExportMode;
use App\Services\Reporting\Temporal\ApplicationResultExportCheckpointStore;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ApplicationResultExportCheckpointStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_complete_checkpoint_is_restored_and_corruption_is_rejected(): void
    {
        $directory = 'report-exports/temporal/test/staging/source';
        $store = app(CanonicalNdjsonStore::class);
        $paths = [
            'applications' => $directory.'/applications.ndjson',
            'changes' => $directory.'/changes.ndjson',
            'documents' => $directory.'/documents.ndjson',
            'findings' => $directory.'/findings.ndjson',
        ];
        $counts = [];
        $checksums = [];
        foreach ($paths as $dataset => $path) {
            $counts[$dataset] = $store->write(
                $path,
                $dataset === 'applications' ? [['application_number' => 'APP-001']] : [],
            );
            $checksums[$dataset] = $store->checksum($path);
        }
        $source = $this->source();
        $snapshot = new ApplicationResultExportSnapshotData(
            source: $source,
            datasetPaths: $paths,
            counts: $counts,
            checksums: $checksums,
            sourceFingerprint: str_repeat('a', 64),
        );
        $checkpoints = app(ApplicationResultExportCheckpointStore::class);
        $metadata = $checkpoints->capture($snapshot);

        $restored = $checkpoints->restore($source, $directory, $metadata);

        $this->assertNotNull($restored);
        $this->assertSame(1, $restored->counts['applications']);
        $this->assertSame(str_repeat('a', 64), $restored->sourceFingerprint);

        Storage::disk('local')->append($paths['applications'], '{"corrupted":true}');

        $this->assertNull($checkpoints->restore($source, $directory, $metadata));
        $this->assertSame([], Storage::disk('local')->allFiles($directory));
    }

    private function source(): ApplicationResultExportSourceData
    {
        return new ApplicationResultExportSourceData(
            mode: ApplicationResultExportMode::CurrentState,
            municipalityId: 1,
            contestId: 2,
            municipalityCode: 'MUNICIPIO-A',
            contestCode: 'CONTEST-001',
            snapshotAt: CarbonImmutable::parse('2026-08-02 10:00:00', 'UTC'),
            official: false,
            sourceType: 'current_state',
            sourceReferences: [],
        );
    }
}
