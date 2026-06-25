<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\HousingVisit;
use App\Models\MunicipalTeam;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Reports\MunicipalKpiService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA45DashboardsKpisMunicipalReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_qa45_municipal_reporting_area_requires_authorized_backoffice_user(): void
    {
        $this->get(route('backoffice.reports.index'))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('candidate'))
            ->get(route('backoffice.reports.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('municipal_technician'))
            ->get(route('backoffice.reports.index'))
            ->assertOk()
            ->assertDontSee('storage_path');
    }

    public function test_qa45_municipal_kpi_snapshot_is_aggregated_and_minimized(): void
    {
        $situation = CurrentHousingSituation::factory()->create([
            'current_parish' => 'Freguesia Demo',
            'current_housing_typology' => 'T2',
        ]);

        Application::factory()->submitted()->create([
            'current_housing_situation_id' => $situation->id,
        ]);
        DocumentSubmission::factory()->create(['status' => 'under_review']);
        HousingVisit::factory()->create(['status' => 'confirmed']);
        SupportTicket::factory()->create(['status' => 'open']);

        $team = MunicipalTeam::factory()->create(['name' => 'Gabinete Técnico']);
        WorkTask::factory()->create([
            'municipal_team_id' => $team->id,
            'status' => WorkTask::STATUS_OVERDUE,
            'due_at' => now()->subDay(),
        ]);
        AuditEvent::factory()->create(['event_category' => 'security', 'severity' => 'warning']);

        $snapshot = app(MunicipalKpiService::class)->snapshot();

        $this->assertSame(1, $snapshot['kpis']['applications_by_status']['submitted']);
        $this->assertSame(1, $snapshot['kpis']['applications_by_typology']['T2']);
        $this->assertSame(1, $snapshot['kpis']['applications_by_parish']['Freguesia Demo']);
        $this->assertSame(1, $snapshot['kpis']['pending_documents']);
        $this->assertSame(1, $snapshot['kpis']['visits_by_status']['confirmed']);
        $this->assertSame(1, $snapshot['kpis']['tickets_by_status']['open']);
        $this->assertSame(1, $snapshot['kpis']['work_tasks_by_team']['Gabinete Técnico']);
        $this->assertSame(1, $snapshot['kpis']['work_tasks_by_sla']['overdue']);
        $this->assertStringNotContainsString('@example.test', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
