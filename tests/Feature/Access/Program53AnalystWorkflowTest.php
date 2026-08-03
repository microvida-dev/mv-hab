<?php

namespace Tests\Feature\Access;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleAssignmentService;
use App\Services\Access\RoleManagementService;
use App\Services\Administrative\ApplicationReviewBatchService;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Administrative\ApplicationReviewReadinessService;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Program53AnalystWorkflowTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    private Contest $contest;

    private User $analyst;

    private AdministrativeProcess $process;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        $this->seed([
            SystemAccessSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
        $this->mock(
            ApplicationReviewReadinessService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('forProcess')->andReturn([
                    'ready' => true,
                    'total_required' => 1,
                    'validated' => 1,
                    'submitted' => 0,
                    'under_review' => 0,
                    'missing' => 0,
                    'rejected' => 0,
                    'expired' => 0,
                    'blockers' => [],
                ]);
            },
        );

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
        $administrator = $this->userWithRole('administrator');
        $role = app(RoleManagementService::class)->applyTemplate(
            $administrator,
            'analista-candidaturas-exportacao',
            'Aplicar o perfil para o fluxo integral do Programa 53.',
        );
        $this->analyst = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
        ]);
        app(RoleAssignmentService::class)->assign(
            $administrator,
            $this->analyst,
            $role,
            'Atribuir o perfil para o fluxo integral do Programa 53.',
        );
        $this->process = $this->readyProcess($program);
    }

    public function test_profile_completes_non_sensitive_program_53_workflow(): void
    {
        $this->actingAs($this->analyst)
            ->withSession(['mfa.verified_at' => now()]);

        $this->get(route(
            'backoffice.application-review-batches.contest',
            $this->contest,
        ))->assertOk();

        $batchPayload = [
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'process_ids' => [$this->process->getKey()],
            'reason' => 'Fecho técnico integral do lote de teste.',
            'preview_token' => null,
        ];
        $batchPreview = app(ApplicationReviewBatchService::class)->preview(
            $this->contest,
            $this->analyst,
            $batchPayload,
        );
        $batchPayload['preview_token'] = $batchPreview['token'];

        $this->post(route(
            'backoffice.application-review-batches.seal',
            $this->contest,
        ), [
            ...$batchPayload,
            'cycle' => $batchPayload['cycle']->value,
        ])->assertSessionHasNoErrors();

        $batch = ApplicationReviewBatch::query()->sole();
        $this->get(route(
            'backoffice.application-review-batches.show',
            $batch,
        ))->assertOk();

        $publicationReason = 'Publicação municipal do lote de teste.';
        $publicationPreview = app(
            ApplicationReviewPublicationService::class,
        )->preview($batch, $this->analyst, $publicationReason);
        $this->post(route(
            'backoffice.application-review-publications.publish',
            $batch,
        ), [
            'reason' => $publicationReason,
            'preview_token' => $publicationPreview['token'],
        ])->assertSessionHasNoErrors();

        $publication = ApplicationReviewPublication::query()->sole();
        $this->get(route(
            'backoffice.application-review-publications.show',
            $publication,
        ))->assertOk();

        $exportPayload = $this->exportPayload();
        $this->post(
            route('backoffice.reports.temporal-exports.store'),
            $exportPayload,
        )->assertSessionHasNoErrors();
        $export = ReportExport::query()->sole();

        app(TemporalApplicationResultExportService::class)
            ->process((int) $export->getKey());
        $export->refresh();

        $this->assertSame(ReportExportStatus::Completed, $export->status);
        Storage::disk('local')->assertExists((string) $export->file_path);
        $this->get(route(
            'backoffice.reports.temporal-exports.download',
            $export,
        ))
            ->assertOk()
            ->assertDownload($export->file_name);
        $this->get(route('backoffice.reports.access-logs.index'))
            ->assertOk();

        $this->assertDatabaseHas('report_download_logs', [
            'report_export_id' => $export->getKey(),
            'user_id' => $this->analyst->getKey(),
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'application_result_export_completed',
            'auditable_id' => $export->getKey(),
        ]);
    }

    public function test_profile_cannot_request_sensitive_export_or_cross_municipal_scope(): void
    {
        $this->actingAs($this->analyst)
            ->withSession(['mfa.verified_at' => now()]);
        $beforeExports = ReportExport::query()->count();

        $this->post(
            route('backoffice.reports.temporal-exports.store'),
            $this->exportPayload([
                'include_sensitive' => '1',
                'sensitive_confirmed' => '1',
            ]),
        )->assertForbidden();
        $this->assertSame($beforeExports, ReportExport::query()->count());

        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $foreignProgram = Program::factory()->create([
            'municipality_id' => $foreignMunicipality->getKey(),
        ]);
        $foreignContest = Contest::factory()->create([
            'program_id' => $foreignProgram->getKey(),
        ]);

        $this->get(route(
            'backoffice.application-review-batches.contest',
            $foreignContest,
        ))->assertForbidden();
        $this->post(
            route('backoffice.reports.temporal-exports.store'),
            $this->exportPayload([
                'contest_id' => $foreignContest->getKey(),
            ]),
        )->assertForbidden();

        $this->assertSame($beforeExports, ReportExport::query()->count());
        $this->assertFalse(
            $this->analyst->hasPermission('reports.export_sensitive'),
        );
    }

    private function readyProcess(Program $program): AdministrativeProcess
    {
        $candidate = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
        ]);
        $application = Application::factory()->submitted()->create([
            'user_id' => $candidate->getKey(),
            'program_id' => $program->getKey(),
            'contest_id' => $this->contest->getKey(),
        ]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->getKey(),
            'program_id' => $program->getKey(),
            'contest_id' => $this->contest->getKey(),
            'user_id' => $candidate->getKey(),
            'assigned_to' => $this->analyst->getKey(),
            'status' => AdministrativeProcessStatus::DocumentReview->value,
        ]);
        $review = new ApplicationReview([
            'review_type' => ApplicationReviewType::Documental,
            'summary' => 'Análise documental pronta para fecho.',
        ]);
        $review->forceFill([
            'administrative_process_id' => $process->getKey(),
            'application_id' => $application->getKey(),
            'status' => ApplicationReviewStatus::ReadyForClosure,
            'reviewed_by' => $this->analyst->getKey(),
            'started_at' => now()->subHour(),
            'ready_for_closure_at' => now(),
            'last_activity_at' => now(),
            'lock_version' => 1,
        ])->save();
        DocumentSubmission::factory()->create([
            'application_id' => $application->getKey(),
            'adhesion_registration_id' => $application
                ->adhesion_registration_id,
            'user_id' => $candidate->getKey(),
            'status' => DocumentStatus::Validated->value,
            'reviewed_at' => now(),
            'validated_at' => now(),
        ]);

        return $process->refresh()->load([
            'application.program',
            'contest',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function exportPayload(array $overrides = []): array
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

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->getKey(),
            'status' => 'active',
        ]);
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}
