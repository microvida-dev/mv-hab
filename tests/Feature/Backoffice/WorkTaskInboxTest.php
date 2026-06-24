<?php

namespace Tests\Feature\Backoffice;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_technician_sees_own_tasks_and_team_queue(): void
    {
        $technician = $this->userWithRole('municipal_technician', 'Gabinete Técnico');
        $team = MunicipalTeam::query()->where('name', 'Gabinete Técnico')->firstOrFail();
        $own = WorkTask::factory()->create([
            'task_number' => 'WTK-QA31-OWN',
            'municipal_team_id' => $team->id,
            'assigned_user_id' => $technician->id,
            'status' => WorkTask::STATUS_ASSIGNED,
        ]);
        WorkTask::factory()->create([
            'task_number' => 'WTK-QA31-TEAM',
            'municipal_team_id' => $team->id,
            'assigned_user_id' => null,
            'status' => WorkTask::STATUS_PENDING,
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.my'))
            ->assertOk()
            ->assertSee($own->task_number)
            ->assertDontSee('WTK-QA31-TEAM');

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.team'))
            ->assertOk()
            ->assertSee('WTK-QA31-OWN')
            ->assertSee('WTK-QA31-TEAM');
    }

    public function test_technician_cannot_open_task_from_other_team(): void
    {
        $technician = $this->userWithRole('municipal_technician', 'Gabinete Técnico');
        $legalTeam = MunicipalTeam::query()->where('name', 'Gabinete Jurídico')->firstOrFail();
        $task = WorkTask::factory()->create([
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
            'municipal_team_id' => $legalTeam->id,
            'assigned_user_id' => null,
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.show', $task))
            ->assertForbidden();
    }

    private function userWithRole(string $role, string $teamName): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);
        MunicipalTeam::query()->where('name', $teamName)->firstOrFail()->members()->syncWithoutDetaching([
            $user->id => ['joined_at' => now(), 'role_in_team' => $role],
        ]);

        return $user;
    }
}
