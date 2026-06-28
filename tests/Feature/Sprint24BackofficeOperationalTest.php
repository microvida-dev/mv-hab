<?php

namespace Tests\Feature;

use App\Enums\DocumentDossierStatus;
use App\Enums\GeneratedProcedureDocumentStatus;
use App\Enums\InternalAlertStatus;
use App\Enums\ListAutomationStatus;
use App\Enums\ProcedureMinuteStatus;
use App\Enums\ProcedureTemplateStatus;
use App\Enums\ProcedureTemplateType;
use App\Enums\ProcessConfirmationStatus;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\Contest;
use App\Models\DocumentDossier;
use App\Models\GeneratedProcedureDocument;
use App\Models\InternalAlert;
use App\Models\ListAutomationRun;
use App\Models\ProcedureMinute;
use App\Models\ProcedureTemplate;
use App\Models\Program;
use App\Models\User;
use App\Services\ListAutomation\ListAutomationRunService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint24BackofficeOperationalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_operational_dashboards_are_protected(): void
    {
        $this->get(route('backoffice.operational.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.operational.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.operational.dashboard'))
            ->assertOk()
            ->assertSee('Painel operacional');

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.operational.executive-dashboard'))
            ->assertOk()
            ->assertSee('Painel executivo');
    }

    public function test_application_report_and_document_dossier_are_generated_in_private_storage(): void
    {
        $admin = $this->userWithRole('administrator');
        $application = $this->submittedApplication();

        $this->actingAs($admin)
            ->post(route('backoffice.applications.report.generate', $application), [
                'format' => 'html',
                'include_documents' => '1',
                'include_timeline' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.applications.report.show', $application));

        $report = ApplicationReport::query()->firstOrFail();
        Storage::disk('local')->assertExists($report->file_path);
        $this->assertStringStartsWith('backoffice/application-reports/', (string) $report->file_path);
        $this->assertStringContainsString('validação final compete aos serviços municipais', Storage::disk('local')->get($report->file_path));

        $this->actingAs($admin)
            ->post(route('backoffice.applications.document-dossier.generate', $application), [
                'include_rejected' => '1',
                'include_expired' => '1',
                'export_format' => 'html',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.applications.document-dossier.show', $application));

        $dossier = DocumentDossier::query()->firstOrFail();
        Storage::disk('local')->assertExists($dossier->file_path);
        $this->assertStringStartsWith('backoffice/document-dossiers/', (string) $dossier->file_path);
    }

    public function test_document_dossier_factory_uses_existing_status_enum(): void
    {
        $dossier = DocumentDossier::factory()->create();

        $this->assertSame(DocumentDossierStatus::Standardized, $dossier->status);
    }

    public function test_procedure_templates_generate_and_approve_documents_and_minutes(): void
    {
        $admin = $this->userWithRole('administrator');
        $application = $this->submittedApplication();

        $this->actingAs($admin)
            ->post(route('backoffice.procedure-templates.store'), [
                'type' => ProcedureTemplateType::ProcedureMinute->value,
                'name' => 'Ata operacional teste',
                'description' => 'Minuta fictícia para teste.',
                'content' => '<p>Ata {{application_number}} {{generated_at}}</p>',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $template = ProcedureTemplate::query()->firstOrFail();
        $this->assertSame(ProcedureTemplateStatus::Draft, $template->status);

        $this->actingAs($admin)
            ->post(route('backoffice.procedure-templates.publish', $template))
            ->assertRedirect();

        $this->assertSame(ProcedureTemplateStatus::Active, $template->refresh()->status);

        $this->actingAs($admin)
            ->post(route('backoffice.procedure-templates.documents.generate', $template), [
                'application_id' => $application->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $document = GeneratedProcedureDocument::query()->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($admin)
            ->post(route('backoffice.generated-documents.issue', $document))
            ->assertRedirect();

        $this->assertSame(GeneratedProcedureDocumentStatus::Approved, $document->refresh()->status);

        $this->actingAs($admin)
            ->post(route('backoffice.procedure-minutes.generate'), [
                'procedure_template_id' => $template->id,
                'application_id' => $application->id,
                'subject' => 'Reunião de acompanhamento',
                'title' => 'Ata de teste',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $minute = ProcedureMinute::query()->firstOrFail();
        $this->assertSame(ProcedureMinuteStatus::Generated, $minute->status);
        Storage::disk('local')->assertExists($minute->file_path);

        $this->actingAs($admin)
            ->post(route('backoffice.procedure-minutes.approve', $minute))
            ->assertRedirect();

        $this->assertSame(ProcedureMinuteStatus::Approved, $minute->refresh()->status);
    }

    public function test_internal_alerts_and_list_automation_require_backoffice_review(): void
    {
        $admin = $this->userWithRole('administrator');
        $alert = InternalAlert::factory()->create(['assigned_to' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('backoffice.internal-alerts.show', $alert))
            ->assertOk()
            ->assertSee($alert->title);

        $this->actingAs($admin)
            ->post(route('backoffice.internal-alerts.resolve', $alert), [
                'resolution_notes' => 'Resolvido em teste.',
            ])
            ->assertRedirect();

        $this->assertSame(InternalAlertStatus::Resolved, $alert->refresh()->status);

        $run = ListAutomationRun::factory()->create();
        app(ListAutomationRunService::class)->approve($run, $admin);

        $this->assertSame(ListAutomationStatus::Approved, $run->refresh()->status);
        $this->assertNotNull($run->approved_at);
    }

    public function test_process_confirmation_generates_unique_number_and_candidate_cannot_access_backoffice(): void
    {
        $admin = $this->userWithRole('administrator');
        $candidate = $this->userWithRole('candidate');
        $application = $this->submittedApplication(['user_id' => $candidate->id]);

        $this->actingAs($candidate)
            ->post(route('backoffice.applications.process-confirmations.generate', $application))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('backoffice.applications.process-confirmations.generate', $application))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $confirmation = $application->processConfirmations()->firstOrFail();
        $this->assertStringStartsWith('HAB-', $confirmation->process_number);
        $this->assertContains($confirmation->status, [ProcessConfirmationStatus::Sent, ProcessConfirmationStatus::Failed, ProcessConfirmationStatus::Generated]);

        $this->actingAs($admin)
            ->post(route('backoffice.process-confirmations.send', $confirmation))
            ->assertRedirect();

        $this->assertSame(ProcessConfirmationStatus::Sent, $confirmation->refresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submittedApplication(array $overrides = []): Application
    {
        $program = Program::factory()->create();
        $contest = Contest::factory()->create(['program_id' => $program->id]);

        return Application::factory()
            ->submitted()
            ->create(array_merge([
                'program_id' => $program->id,
                'contest_id' => $contest->id,
            ], $overrides));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
