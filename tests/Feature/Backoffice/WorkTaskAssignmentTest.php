<?php

namespace Tests\Feature\Backoffice;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskAssignmentService;
use App\Services\Workflows\WorkTaskCreationService;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_assignment_uses_least_loaded_compatible_active_user(): void
    {
        $actor = $this->userWithRole('administrator');
        $loaded = $this->userWithRole('legal_manager', 'Gabinete Jurídico');
        $available = $this->userWithRole('legal_manager', 'Gabinete Jurídico');

        WorkTask::factory()->count(3)->create([
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
            'assigned_user_id' => $loaded->id,
            'status' => WorkTask::STATUS_ASSIGNED,
        ]);

        $task = app(WorkTaskCreationService::class)->createFromSource(
            type: WorkTask::TYPE_COMPLAINT_REVIEW,
            actor: $actor,
            source: 'complaint_submitted',
        );

        $this->assertSame($available->id, $task->assigned_user_id);
    }

    public function test_inactive_team_or_user_is_not_assigned(): void
    {
        $actor = $this->userWithRole('administrator');
        $inactive = $this->userWithRole('maintenance_manager', 'Manutenção', ['deactivated_at' => now()]);

        $task = app(WorkTaskCreationService::class)->createFromSource(
            type: WorkTask::TYPE_MAINTENANCE_TRIAGE,
            actor: $actor,
            source: 'maintenance_request_created',
        );

        $this->assertSame('Manutenção', $task->municipalTeam?->name);
        $this->assertNotSame($inactive->id, $task->assigned_user_id);
        $this->assertNull($task->assigned_user_id);

        MunicipalTeam::query()->where('name', 'Manutenção')->update(['status' => 'inactive']);

        $second = app(WorkTaskCreationService::class)->createFromSource(
            type: WorkTask::TYPE_MAINTENANCE_TRIAGE,
            actor: $actor,
            source: 'maintenance_request_created_second',
        );

        $this->assertNull($second->municipal_team_id);
        $this->assertNull($second->assigned_user_id);
        $this->assertSame(WorkTask::STATUS_PENDING, $second->status);
    }

    public function test_reassignment_requires_reason(): void
    {
        $actor = $this->userWithRole('administrator');
        $support = $this->userWithRole('support_agent', 'Atendimento');
        $team = MunicipalTeam::query()->where('name', 'Atendimento')->firstOrFail();
        $task = WorkTask::factory()->create(['type' => WorkTask::TYPE_SUPPORT_TICKET]);

        $this->expectException(DomainException::class);
        app(WorkTaskAssignmentService::class)->reassign($task, $actor, $team, $support, '');
    }

    public function test_reassignment_rejects_incompatible_profile(): void
    {
        $actor = $this->userWithRole('administrator');
        $support = $this->userWithRole('support_agent', 'Atendimento');
        $team = MunicipalTeam::query()->where('name', 'Atendimento')->firstOrFail();
        $task = WorkTask::factory()->create(['type' => WorkTask::TYPE_COMPLAINT_REVIEW]);

        $this->expectException(DomainException::class);
        app(WorkTaskAssignmentService::class)->reassign($task, $actor, $team, $support, 'Tentativa de perfil incompatível.');
    }

    private function userWithRole(string $role, ?string $teamName = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['status' => 'active'], $attributes));
        $user->assignRole($role);

        if ($teamName !== null) {
            MunicipalTeam::query()->where('name', $teamName)->firstOrFail()->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $role],
            ]);
        }

        return $user;
    }
}
