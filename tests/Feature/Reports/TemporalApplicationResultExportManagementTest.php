<?php

namespace Tests\Feature\Reports;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Jobs\GenerateApplicationResultExport;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;
use ZipArchive;

class TemporalApplicationResultExportManagementTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    private Contest $contest;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
        $this->municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $program = Program::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
        ]);
        $this->contest = Contest::factory()->create([
            'program_id' => $program->getKey(),
        ]);
        $this->administrator = $this->userWithRole('administrator');
    }

    public function test_administrator_can_preview_without_generating_files(): void
    {
        $response = $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('backoffice.reports.temporal-exports.preview'),
                $this->payload(),
            );

        $response
            ->assertOk()
            ->assertSee('Pré-visualização')
            ->assertSee($this->contest->code)
            ->assertSee('Operacional')
            ->assertSee('Formatos')
            ->assertSee('CSV, JSON')
            ->assertSee('Datasets')
            ->assertSee('Candidaturas, Documentos, Achados')
            ->assertSee('Referências da origem')
            ->assertSee('Impacto esperado')
            ->assertSee('não gerou ficheiros');
        $this->assertDatabaseCount('report_exports', 0);
        $this->assertTrue(AuditEvent::query()
            ->where('event_code', 'application_result_export_previewed')
            ->exists());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_request_is_queued_after_persistence_and_is_idempotent(): void
    {
        Queue::fake();
        $payload = $this->payload();

        $first = $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $payload);
        $export = ReportExport::query()->firstOrFail();

        $first
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.reports.temporal-exports.show', $export));
        $this->assertSame(ReportExportStatus::Pending, $export->status);
        $this->assertSame($this->municipality->getKey(), $export->municipality_id);
        $this->assertSame($this->contest->getKey(), $export->contest_id);
        $this->assertSame(TemporalApplicationResultExportService::PROFILE, $export->export_profile);
        $this->assertNotEmpty($export->idempotency_key);
        Queue::assertPushed(
            GenerateApplicationResultExport::class,
            fn (GenerateApplicationResultExport $job): bool => $job->reportExportId === $export->getKey(),
        );

        $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $payload)
            ->assertRedirect(route('backoffice.reports.temporal-exports.show', $export));

        $this->assertDatabaseCount('report_exports', 1);
        $this->assertDatabaseCount('report_runs', 1);
        Queue::assertPushed(GenerateApplicationResultExport::class, 1);
    }

    public function test_worker_generates_valid_private_package_and_download_is_audited(): void
    {
        Queue::fake();
        Application::factory()->submitted()->create([
            'program_id' => $this->contest->program_id,
            'contest_id' => $this->contest->getKey(),
        ]);
        $payload = $this->payload([
            'formats' => array_map(
                static fn (ApplicationResultExportFormat $format): string => $format->value,
                ApplicationResultExportFormat::cases(),
            ),
        ]);

        $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $payload)
            ->assertSessionHasNoErrors();
        $export = ReportExport::query()->firstOrFail();

        app(TemporalApplicationResultExportService::class)
            ->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        $this->assertSame(100, $export->progress);
        $this->assertNotNull($export->snapshot_at);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->source_fingerprint);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->manifest_sha256);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $export->package_sha256);
        $this->assertStringEndsWith('.zip', (string) $export->file_name);
        $this->assertStringNotContainsString('..', (string) $export->file_path);
        Storage::disk('local')->assertExists((string) $export->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles(
            'report-exports/temporal/'.$export->public_id.'/staging',
        ));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path((string) $export->file_path)) === true);
        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entries[] = $zip->getNameIndex($index);
        }
        $zip->close();
        $this->assertContains('manifest.json', $entries);
        $this->assertContains('checksums.sha256', $entries);
        $this->assertContains('applications.csv', $entries);
        $this->assertContains('applications.json', $entries);
        $this->assertContains('applications.xml', $entries);
        $this->assertContains('applications.xlsx', $entries);

        $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.download', $export))
            ->assertOk()
            ->assertDownload($export->file_name);

        $this->assertDatabaseHas('report_download_logs', [
            'report_export_id' => $export->getKey(),
            'user_id' => $this->administrator->getKey(),
        ]);
        $this->assertTrue(AuditEvent::query()
            ->where('event_code', 'application_result_export_completed')
            ->where('auditable_id', $export->getKey())
            ->exists());
        $this->assertTrue(AuditEvent::query()
            ->where('event_code', 'application_result_export_downloaded')
            ->where('auditable_id', $export->getKey())
            ->exists());
    }

    public function test_completed_export_is_not_regenerated_by_duplicate_worker(): void
    {
        Queue::fake();
        $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload());
        $export = ReportExport::query()->firstOrFail();
        $service = app(TemporalApplicationResultExportService::class);

        $service->process((int) $export->getKey());
        $firstHash = $export->refresh()->package_sha256;
        $firstCompletedAt = $export->completed_at?->toISOString();
        $service->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame($firstHash, $export->package_sha256);
        $this->assertSame($firstCompletedAt, $export->completed_at?->toISOString());
        $this->assertCount(1, Storage::disk('local')->allFiles(
            dirname((string) $export->file_path),
        ));
    }

    public function test_authorized_sensitive_dossier_request_is_packaged_fail_closed(): void
    {
        Queue::fake();

        $this->actingAs($this->administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.temporal-exports.store'), $this->payload([
                'formats' => [ApplicationResultExportFormat::Csv->value],
                'include_sensitive' => '1',
                'sensitive_confirmed' => '1',
                'include_document_files' => '1',
                'document_files_confirmed' => '1',
            ]))
            ->assertSessionHasNoErrors();
        $export = ReportExport::query()->firstOrFail();

        $this->assertTrue($export->sensitive_fields_included);
        $this->assertTrue($export->document_files_requested);
        app(TemporalApplicationResultExportService::class)
            ->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        $this->assertFalse($export->document_files_included);
        $this->assertContains(
            'Os binários documentais foram excluídos: o repositório não possui um estado confiável de antivírus/quarentena.',
            $export->source_metadata['warnings'] ?? [],
        );

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path((string) $export->file_path)) === true);
        $this->assertNotFalse($zip->locateName('document-index.csv'));
        $this->assertFalse(collect(range(0, $zip->numFiles - 1))->contains(
            static fn (int $index): bool => str_starts_with(
                (string) $zip->getNameIndex($index),
                'documents/',
            ),
        ));
        $zip->close();

        $this->assertTrue(AuditEvent::query()
            ->where('event_code', 'sensitive_application_result_export_requested')
            ->where('auditable_id', $export->getKey())
            ->exists());
        $this->assertTrue(AuditEvent::query()
            ->where('event_code', 'document_dossier_export_requested')
            ->where('auditable_id', $export->getKey())
            ->exists());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'contest_id' => $this->contest->getKey(),
            'mode' => ApplicationResultExportMode::CurrentState->value,
            'formats' => [
                ApplicationResultExportFormat::Csv->value,
                ApplicationResultExportFormat::Json->value,
            ],
            'datasets' => [
                ApplicationResultExportDataset::Applications->value,
                ApplicationResultExportDataset::Documents->value,
                ApplicationResultExportDataset::Findings->value,
            ],
            'csv_delimiter' => 'semicolon',
            'csv_bom' => '1',
            'include_sensitive' => '0',
            'include_document_files' => '0',
            'changed_documents_only' => '0',
            'include_unchanged' => '0',
            'idempotency_token' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
