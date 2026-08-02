<?php

namespace Tests\Feature\Reports;

use App\Contracts\Program53\Program53FaultInjector;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ExportScope;
use App\Enums\Program53FailureCode;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Program53\Resilience\ControlledProgram53FaultInjector;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TemporalApplicationResultExportRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
    }

    public function test_retry_reuses_complete_snapshot_after_transient_failure(): void
    {
        $export = $this->export();
        $injector = new ControlledProgram53FaultInjector([
            'after_snapshot_checksum' => Program53FailureCode::StorageUnavailable,
        ]);
        $this->app->instance(Program53FaultInjector::class, $injector);
        $service = $this->app->make(TemporalApplicationResultExportService::class);

        try {
            $service->process((int) $export->getKey());
            $this->fail('A primeira tentativa deveria falhar no checkpoint.');
        } catch (\Throwable) {
            // O job real volta a lançar para ativar retry com backoff.
        }

        $export->refresh();
        $this->assertSame(ReportExportStatus::Failed, $export->status);
        $this->assertSame('storage_unavailable', $export->failure_code);
        $this->assertIsArray(data_get($export->source_metadata, 'export_checkpoint'));
        $this->assertNotEmpty(Storage::disk('local')->allFiles(
            'report-exports/temporal/'.$export->public_id.'/staging/source',
        ));

        $service->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        $this->assertTrue((bool) data_get($export->source_metadata, 'snapshot_reused'));
        $this->assertSame(2, data_get($export->source_metadata, 'operational.attempt'));
        Storage::disk('local')->assertExists((string) $export->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles(
            'report-exports/temporal/'.$export->public_id.'/staging',
        ));
    }

    public function test_corrupted_snapshot_is_discarded_and_rebuilt_on_retry(): void
    {
        $export = $this->export();
        $injector = new ControlledProgram53FaultInjector([
            'after_snapshot_checksum' => Program53FailureCode::StorageUnavailable,
        ]);
        $this->app->instance(Program53FaultInjector::class, $injector);
        $service = $this->app->make(TemporalApplicationResultExportService::class);

        try {
            $service->process((int) $export->getKey());
        } catch (\Throwable) {
            // Falha transitória controlada.
        }

        $sourcePath = 'report-exports/temporal/'.$export->public_id
            .'/staging/source/applications.ndjson';
        Storage::disk('local')->append($sourcePath, '{"invalid_checkpoint":true}');

        $service->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        $this->assertFalse((bool) data_get($export->source_metadata, 'snapshot_reused'));
        Storage::disk('local')->assertExists((string) $export->file_path);
    }

    public function test_expired_export_with_interrupted_cleanup_can_resume_cleanup_only(): void
    {
        $export = $this->export(ReportExportStatus::Completed);
        $export->forceFill([
            'expires_at' => now()->subMinute(),
            'downloaded_at' => now()->subHour(),
            'file_path' => 'reports/tests/expiry/export.zip',
            'file_name' => 'export.zip',
        ])->save();
        Storage::disk('local')->put((string) $export->file_path, 'package');
        $injector = new ControlledProgram53FaultInjector([
            'after_database_expired_before_file_delete' => Program53FailureCode::StorageUnavailable,
        ]);
        $this->app->instance(Program53FaultInjector::class, $injector);
        $service = $this->app->make(TemporalApplicationResultExportService::class);

        try {
            $service->expire((int) $export->getKey());
            $this->fail('A primeira limpeza deveria ser interrompida.');
        } catch (\Throwable) {
            // O estado expirado impede imediatamente qualquer download.
        }

        $this->assertSame(ReportExportStatus::Expired, $export->refresh()->status);
        Storage::disk('local')->assertExists((string) $export->file_path);

        $this->assertTrue($service->expire((int) $export->getKey()));
        $export->refresh();

        $this->assertSame('', $export->file_path);
        $this->assertNull($export->file_size);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_duplicate_scheduler_runs_expire_once_without_duplicate_audit(): void
    {
        $export = $this->export(ReportExportStatus::Completed);
        $export->forceFill([
            'expires_at' => now()->subMinute(),
            'downloaded_at' => now()->subHour(),
            'file_path' => 'reports/tests/scheduler/export.zip',
            'file_name' => 'export.zip',
        ])->save();
        Storage::disk('local')->put((string) $export->file_path, 'package');

        $this->assertSame(0, Artisan::call('reports:expire-temporal-exports'));
        $this->assertSame(0, Artisan::call('reports:expire-temporal-exports'));

        $this->assertSame(ReportExportStatus::Expired, $export->refresh()->status);
        $this->assertSame('', $export->file_path);
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'application_result_export_expired',
            'auditable_id' => $export->getKey(),
        ]);
    }

    private function export(
        ReportExportStatus $status = ReportExportStatus::Pending,
    ): ReportExport {
        $municipality = Municipality::factory()->create();
        $program = Program::factory()->create([
            'municipality_id' => $municipality->getKey(),
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->getKey(),
        ]);
        $user = User::factory()->create([
            'municipality_id' => $municipality->getKey(),
        ]);
        Application::factory()->submitted()->create([
            'program_id' => $program->getKey(),
            'contest_id' => $contest->getKey(),
            'user_id' => $user->getKey(),
        ]);
        $definition = ReportDefinition::query()
            ->where('code', TemporalApplicationResultExportService::REPORT_CODE)
            ->firstOrFail();
        $run = ReportRun::factory()->create([
            'report_definition_id' => $definition->getKey(),
            'user_id' => $user->getKey(),
            'status' => $status === ReportExportStatus::Completed
                ? ReportRunStatus::Completed
                : ReportRunStatus::Started,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'filters' => [],
        ]);

        return ReportExport::factory()->create([
            'report_run_id' => $run->getKey(),
            'user_id' => $user->getKey(),
            'municipality_id' => $municipality->getKey(),
            'contest_id' => $contest->getKey(),
            'export_profile' => TemporalApplicationResultExportService::PROFILE,
            'export_mode' => ApplicationResultExportMode::CurrentState,
            'status' => $status,
            'requested_format' => ReportFormat::Zip,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'file_path' => '',
            'file_name' => '',
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [ApplicationResultExportDataset::Applications->value],
            'source_metadata' => [
                'parameters' => [],
                'request_options' => [],
                'operational' => [
                    'operation_id' => 'recovery-'.$contest->getKey(),
                    'attempt' => 0,
                ],
            ],
        ]);
    }
}
