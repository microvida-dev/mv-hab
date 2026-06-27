<?php

namespace Tests\Unit\Dashboard;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Dashboard\DashboardMetricService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_assigned_and_overdue_work_task_metrics_are_aggregated_for_user(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('municipal_technician');

        $team = MunicipalTeam::factory()->create();
        $user->municipalTeams()->attach($team->id, ['joined_at' => now()]);

        WorkTask::factory()
            ->assigned($user)
            ->create([
                'municipal_team_id' => $team->id,
                'due_at' => now()->subDay(),
            ]);

        $metrics = collect(app(DashboardMetricService::class)->forUser($user))->keyBy('key');

        $this->assertSame(1, $metrics->get('assigned_tasks')['value']);
        $this->assertSame(1, $metrics->get('team_tasks')['value']);
        $this->assertSame(1, $metrics->get('overdue_tasks')['value']);
    }
}
