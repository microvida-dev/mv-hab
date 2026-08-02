<?php

namespace Tests\Feature\Console;

use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use App\Services\Program53\Operations\Program53OperationalHealthService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;
use Tests\TestCase;

final class Program53OperationalCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
    }

    /** @throws JsonException */
    public function test_json_check_is_read_only_and_contains_severity_summary(): void
    {
        $before = $this->databaseState();

        $exit = Artisan::call('program53:operational-check', [
            '--format' => 'json',
            '--fail-on-critical' => true,
        ]);
        $payload = json_decode(
            Artisan::output(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame(0, $exit);
        $this->assertSame('1.0', data_get($payload, 'schema_version'));
        $this->assertSame(0, data_get($payload, 'summary.critical'));
        $this->assertGreaterThan(10, data_get($payload, 'summary.total'));
        $this->assertSame($before, $this->databaseState());
        $this->assertSame([], Storage::disk('local')->allFiles('program53-health'));
    }

    public function test_invalid_output_path_is_rejected(): void
    {
        $exit = Artisan::call('program53:operational-check', [
            '--format' => 'markdown',
            '--output' => '../health.md',
        ]);

        $this->assertSame(2, $exit);
    }

    public function test_corrupted_completed_package_is_reported_as_critical_without_mutation(): void
    {
        $export = ReportExport::factory()->create([
            'export_profile' => TemporalApplicationResultExportService::PROFILE,
            'status' => ReportExportStatus::Completed,
            'source_fingerprint' => str_repeat('a', 64),
            'manifest_sha256' => str_repeat('b', 64),
            'package_sha256' => str_repeat('c', 64),
        ]);
        Storage::disk('local')->put((string) $export->file_path, 'corrupted');
        $before = $this->databaseState();

        $exit = Artisan::call('program53:operational-check', [
            '--format' => 'json',
            '--fail-on-critical' => true,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString(
            'exports.completed_packages',
            Artisan::output(),
        );
        $this->assertSame($before, $this->databaseState());
        Storage::disk('local')->assertExists((string) $export->file_path);
    }

    public function test_cache_lock_failure_is_reported_fail_closed(): void
    {
        $originalCache = Cache::getFacadeRoot();
        Cache::swap(new class(app()) extends CacheManager
        {
            public function get(string $key): mixed
            {
                unset($key);

                return null;
            }

            public function lock(string $key, int $seconds): never
            {
                unset($key, $seconds);

                throw new RuntimeException('controlled-cache-failure');
            }

            public function forget(string $key): bool
            {
                unset($key);

                return true;
            }
        });

        try {
            $result = app(Program53OperationalHealthService::class)->inspect();
        } finally {
            Cache::swap($originalCache);
        }

        $finding = collect($result['findings'])
            ->firstWhere('code', 'cache.atomic_locks');
        $this->assertIsArray($finding);
        $this->assertSame('critical', $finding['severity']);
    }

    /** @return array<string, int> */
    private function databaseState(): array
    {
        return [
            'audit_events' => (int) DB::table('audit_events')->count(),
            'audit_logs' => (int) DB::table('audit_logs')->count(),
            'report_exports' => (int) DB::table('report_exports')->count(),
            'failed_jobs' => (int) DB::table('failed_jobs')->count(),
        ];
    }
}
