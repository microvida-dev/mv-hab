<?php

namespace Tests\Unit\Reporting\Temporal;

use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CanonicalNdjsonStoreTest extends TestCase
{
    public function test_store_writes_reads_and_rewrites_canonical_private_rows(): void
    {
        Storage::fake('local');
        $store = app(CanonicalNdjsonStore::class);
        $store->createDirectory('report-exports/test');
        $store->createDirectory('report-exports/test');

        $count = $store->write('report-exports/test/applications.ndjson', [
            ['z' => 1, 'a' => 'primeira'],
            ['a' => 'segunda', 'z' => 2],
        ]);

        $this->assertSame(2, $count);
        $this->assertSame([
            ['a' => 'primeira', 'z' => 1],
            ['a' => 'segunda', 'z' => 2],
        ], iterator_to_array($store->rows('report-exports/test/applications.ndjson'), false));
        $initialChecksum = $store->checksum('report-exports/test/applications.ndjson');

        $store->rewrite(
            'report-exports/test/applications.ndjson',
            static fn (array $row): array => [...$row, 'schema_version' => '1.0'],
        );

        $this->assertNotSame(
            $initialChecksum,
            $store->checksum('report-exports/test/applications.ndjson'),
        );
        Storage::disk('local')->assertExists('report-exports/test/applications.ndjson');
    }
}
