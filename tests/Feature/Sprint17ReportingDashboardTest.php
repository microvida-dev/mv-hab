<?php

namespace Tests\Feature;

use App\Enums\ExportScope;
use App\Enums\ReportSensitivityLevel;
use App\Models\Application;
use App\Models\Contest;
use App\Models\IndicatorDefinition;
use App\Models\Program;
use App\Models\ReportAccessLog;
use App\Models\ReportDefinition;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Reporting\IndicatorCalculationService;
use Database\Seeders\DashboardDefinitionSeeder;
use Database\Seeders\DashboardWidgetSeeder;
use Database\Seeders\IndicatorDefinitionSeeder;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint17ReportingDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed([
            SystemAccessSeeder::class,
            IndicatorDefinitionSeeder::class,
            DashboardDefinitionSeeder::class,
            DashboardWidgetSeeder::class,
            ReportDefinitionSeeder::class,
        ]);
    }

    public function test_reporting_area_blocks_guest_and_candidate(): void
    {
        $this->get(route('backoffice.reports.index'))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.reports.index'))
            ->assertForbidden();
    }

    public function test_operational_dashboard_is_available_to_technician_but_executive_requires_specific_permission(): void
    {
        $technician = $this->userWithRole('municipal_technician');

        $this->actingAs($technician)
            ->get(route('backoffice.reports.operational'))
            ->assertOk()
            ->assertSee('Dashboard operacional')
            ->assertDontSee('reports.view_financial');

        $this->actingAs($technician)
            ->get(route('backoffice.reports.executive'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->get(route('backoffice.reports.executive'))
            ->assertOk()
            ->assertSee('Dashboard executivo');
    }

    public function test_indicators_apply_program_and_contest_filters(): void
    {
        $user = $this->userWithRole('administrator');
        $program = Program::factory()->create();
        $contest = Contest::factory()->create(['program_id' => $program->id]);
        Application::factory()->count(2)->submitted()->create(['program_id' => $program->id, 'contest_id' => $contest->id]);
        Application::factory()->submitted()->create();
        $indicator = IndicatorDefinition::query()->where('code', 'applications_submitted')->firstOrFail();

        $result = app(IndicatorCalculationService::class)->calculate($indicator, [
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ], $user, true);

        $this->assertSame('available', $result['status']);
        $this->assertSame(2, $result['value']);
        $this->assertDatabaseCount('indicator_snapshots', 1);
    }

    public function test_report_run_records_filters_access_and_audit_without_personal_data(): void
    {
        $admin = $this->userWithRole('administrator');
        $program = Program::factory()->create();
        $contest = Contest::factory()->create(['program_id' => $program->id]);
        $application = Application::factory()->submitted()->create(['program_id' => $program->id, 'contest_id' => $contest->id]);
        $report = ReportDefinition::query()->where('code', 'applications_by_contest')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('backoffice.reports.runs.store', $report), [
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'format' => 'html',
            'scope' => 'aggregated',
        ]);

        $run = ReportRun::query()->firstOrFail();
        $response->assertRedirect(route('backoffice.reports.runs.show', $run));
        $this->assertSame($program->id, $run->filters['program_id']);
        $this->assertDatabaseHas('report_access_logs', ['report_run_id' => $run->id, 'access_type' => 'run_report']);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => $run->getMorphClass(), 'auditable_id' => $run->id]);

        $this->actingAs($admin)
            ->get(route('backoffice.reports.runs.show', $run))
            ->assertOk()
            ->assertSee($contest->title)
            ->assertDontSee($application->user->email);
    }

    public function test_csv_export_is_private_formula_safe_and_download_is_logged(): void
    {
        $admin = $this->userWithRole('administrator');
        $report = ReportDefinition::query()->where('code', 'application_status_summary')->firstOrFail();
        Application::factory()->submitted()->create(['candidate_notes' => '=unsafe']);

        $this->actingAs($admin)->post(route('backoffice.reports.exports.store', $report), [
            'format' => 'csv',
            'scope' => ExportScope::Aggregated->value,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $export = ReportExport::query()->firstOrFail();
        Storage::disk('local')->assertExists($export->file_path);
        $this->assertStringStartsWith('reports/', $export->file_path);
        $this->assertStringNotContainsString('..', $export->file_path);

        $this->actingAs($admin)
            ->get(route('backoffice.reports.exports.download', $export))
            ->assertOk();

        $this->assertSame(1, ReportDownloadLog::query()->count());
        $this->assertTrue(ReportAccessLog::query()->where('access_type', 'download_export')->exists());
    }

    public function test_sensitive_financial_report_is_blocked_for_technician_and_requires_confirmation(): void
    {
        $report = ReportDefinition::query()->where('code', 'financial_arrears_report')->firstOrFail();

        $this->actingAs($this->userWithRole('municipal_technician'))
            ->get(route('backoffice.reports.definitions.show', $report))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('administrator'))
            ->post(route('backoffice.reports.exports.store', $report), [
                'date_from' => now()->startOfMonth()->toDateString(),
                'format' => 'csv',
                'scope' => 'aggregated',
            ])
            ->assertSessionHasErrors('confirmed');
    }

    public function test_xlsx_and_pdf_requests_use_documented_safe_fallbacks(): void
    {
        $admin = $this->userWithRole('administrator');
        $report = ReportDefinition::query()->where('code', 'application_status_summary')->firstOrFail();

        foreach (['xlsx' => 'csv', 'pdf' => 'html'] as $requested => $actual) {
            $this->actingAs($admin)->post(route('backoffice.reports.exports.store', $report), [
                'format' => $requested,
                'scope' => 'aggregated',
            ])->assertSessionHasNoErrors()->assertRedirect();

            $export = ReportExport::query()->latest('id')->firstOrFail();
            $this->assertSame($requested, $export->requested_format->value);
            $this->assertSame($actual, $export->format->value);
        }
    }

    public function test_sensitive_fields_are_guarded_and_auditor_is_read_only(): void
    {
        $report = ReportDefinition::factory()->create();
        $report->fill([
            'sensitivity_level' => ReportSensitivityLevel::HighlySensitive->value,
            'required_permission' => 'reports.view_financial',
            'query_method' => 'financialArrears',
        ]);

        $this->assertNotSame(ReportSensitivityLevel::HighlySensitive, $report->sensitivity_level);
        $this->assertNull($report->required_permission);
        $this->assertSame('applicationStatusSummary', $report->query_method);

        $auditor = $this->userWithRole('auditor');
        $this->actingAs($auditor)->get(route('backoffice.reports.access-logs.index'))->assertOk();
        $this->actingAs($auditor)->post(route('backoffice.reports.definitions.store'), [])->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
