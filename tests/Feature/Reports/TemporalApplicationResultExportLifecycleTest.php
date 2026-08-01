<?php

namespace Tests\Feature\Reports;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ExportScope;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class TemporalApplicationResultExportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
    }

    public function test_expiration_removes_final_and_staging_artifacts(): void
    {
        [$export, $user] = $this->export(ReportExportStatus::Completed);
        $export->forceFill([
            'expires_at' => now()->subMinute(),
            'downloaded_at' => now()->subHour(),
        ])->save();
        Storage::disk('local')->put((string) $export->file_path, 'package');
        Storage::disk('local')->put(
            'report-exports/temporal/'.$export->public_id.'/staging/source/applications.ndjson',
            '{}',
        );

        $expired = app(TemporalApplicationResultExportService::class)
            ->expire((int) $export->getKey());

        $this->assertTrue($expired);
        $export->refresh();
        $this->assertSame(ReportExportStatus::Expired, $export->status);
        $this->assertSame('', $export->file_path);
        $this->assertNull($export->file_size);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'application_result_export_expired',
            'auditable_id' => $export->getKey(),
            'subject_user_id' => $user->getKey(),
        ]);
    }

    public function test_recent_download_defers_expiration_to_avoid_stream_race(): void
    {
        [$export] = $this->export(ReportExportStatus::Completed);
        $export->forceFill([
            'expires_at' => now()->subMinute(),
            'downloaded_at' => now(),
        ])->save();
        Storage::disk('local')->put((string) $export->file_path, 'package');

        $this->assertFalse(app(TemporalApplicationResultExportService::class)
            ->expire((int) $export->getKey()));
        $this->assertSame(ReportExportStatus::Completed, $export->refresh()->status);
        Storage::disk('local')->assertExists((string) $export->file_path);
    }

    public function test_migration_rollback_fails_closed_when_temporal_exports_exist(): void
    {
        $this->export(ReportExportStatus::Completed);
        $migration = require database_path(
            'migrations/2026_08_01_000054_extend_report_exports_for_temporal_application_results.php',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('existem exportações temporais');

        $migration->down();
    }

    public function test_failed_retry_state_has_safe_code_and_no_residual_file(): void
    {
        [$export] = $this->export(ReportExportStatus::Pending);
        $export->forceFill([
            'source_metadata' => ['parameters' => []],
            'file_path' => '',
            'file_name' => '',
        ])->save();

        try {
            app(TemporalApplicationResultExportService::class)
                ->process((int) $export->getKey());
            $this->fail('A origem inválida deveria falhar de forma controlada.');
        } catch (\Throwable) {
            // O job real volta a lançar para ativar a política de retry da fila.
        }

        $export->refresh();
        $this->assertSame(ReportExportStatus::Failed, $export->status);
        $this->assertContains($export->failure_code, [
            'source_not_found',
            'export_generation_failed',
        ]);
        $this->assertStringNotContainsString('/', (string) $export->error_message);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $export->forceFill([
            'export_mode' => ApplicationResultExportMode::CurrentState,
            'source_metadata' => ['parameters' => []],
        ])->save();
        app(TemporalApplicationResultExportService::class)
            ->process((int) $export->getKey());

        $this->assertSame(ReportExportStatus::Completed, $export->refresh()->status);
        Storage::disk('local')->assertExists((string) $export->file_path);
    }

    /** @return array{ReportExport, User} */
    private function export(ReportExportStatus $status): array
    {
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
        $export = ReportExport::factory()->create([
            'report_run_id' => $run->getKey(),
            'user_id' => $user->getKey(),
            'municipality_id' => $municipality->getKey(),
            'contest_id' => $contest->getKey(),
            'export_profile' => TemporalApplicationResultExportService::PROFILE,
            'export_mode' => ApplicationResultExportMode::SealedBatch,
            'status' => $status,
            'requested_format' => ReportFormat::Zip,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'file_path' => 'reports/tests/'.$status->value.'/export.zip',
            'file_name' => 'export.zip',
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [ApplicationResultExportDataset::Applications->value],
            'source_metadata' => ['parameters' => []],
        ]);

        return [$export, $user];
    }
}
