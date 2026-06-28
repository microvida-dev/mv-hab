<?php

namespace Tests\Feature\Backoffice;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_dashboard_shows_status_and_sla_metrics(): void
    {
        $administrator = $this->userWithRole('administrator');
        $team = MunicipalTeam::query()->where('name', 'Gabinete Técnico')->firstOrFail();

        WorkTask::factory()->create([
            'municipal_team_id' => $team->id,
            'status' => WorkTask::STATUS_PENDING,
            'due_at' => now()->addDay(),
        ]);
        WorkTask::factory()->create([
            'municipal_team_id' => $team->id,
            'status' => WorkTask::STATUS_OVERDUE,
            'due_at' => now()->subDay(),
        ]);
        WorkTask::factory()->create([
            'municipal_team_id' => $team->id,
            'status' => WorkTask::STATUS_COMPLETED,
            'due_at' => now()->addDay(),
            'completed_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.dashboard'))
            ->assertOk()
            ->assertSee('Painel de tarefas')
            ->assertSee('Cumprimento SLA');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
