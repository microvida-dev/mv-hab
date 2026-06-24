<?php

namespace Tests\Feature\Security;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_auditor_can_view_but_not_mutate_work_task(): void
    {
        $auditor = $this->userWithRole('auditor', 'Auditoria');
        $task = WorkTask::factory()->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.work-tasks.show', $task))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.work-tasks.claim', $task))
            ->assertForbidden();
    }

    public function test_support_agent_cannot_complete_legal_task_even_if_misassigned(): void
    {
        $support = $this->userWithRole('support_agent', 'Atendimento');
        $legalTeam = MunicipalTeam::query()->where('name', 'Gabinete Jurídico')->firstOrFail();
        $task = WorkTask::factory()->create([
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
            'municipal_team_id' => $legalTeam->id,
            'assigned_user_id' => $support->id,
            'status' => WorkTask::STATUS_ASSIGNED,
        ]);

        $this->actingAs($support)
            ->post(route('backoffice.work-tasks.status', $task), [
                'status' => WorkTask::STATUS_COMPLETED,
                'outcome_note' => 'Conclusão indevida.',
            ])
            ->assertForbidden();

        $this->assertSame(WorkTask::STATUS_ASSIGNED, $task->refresh()->status);
    }

    public function test_candidate_cannot_access_work_task_routes(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('backoffice.work-tasks.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_reassign_task_from_other_team_without_visibility(): void
    {
        $legal = $this->userWithRole('legal_manager', 'Gabinete Jurídico');
        $maintenanceTeam = MunicipalTeam::query()->where('name', 'Manutenção')->firstOrFail();
        $task = WorkTask::factory()->create([
            'type' => WorkTask::TYPE_MAINTENANCE_TRIAGE,
            'municipal_team_id' => $maintenanceTeam->id,
            'assigned_user_id' => null,
            'status' => WorkTask::STATUS_ASSIGNED,
        ]);

        $this->actingAs($legal)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.work-tasks.reassign', $task), [
                'municipal_team_id' => $maintenanceTeam->id,
                'reason' => 'Tentativa de reatribuição fora da equipa.',
            ])
            ->assertForbidden();
    }

    private function userWithRole(string $role, ?string $teamName = null): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        if ($teamName !== null) {
            MunicipalTeam::query()->where('name', $teamName)->firstOrFail()->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $role],
            ]);
        }

        return $user;
    }
}
