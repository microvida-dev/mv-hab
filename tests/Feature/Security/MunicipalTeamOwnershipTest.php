<?php

namespace Tests\Feature\Security;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Policies\WorkTaskPolicy;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalTeamOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_user_only_views_team_task_when_member_or_authorized(): void
    {
        $legalUser = $this->userWithRole('legal_manager', 'Gabinete Jurídico');
        $supportUser = $this->userWithRole('support_agent', 'Atendimento');
        $legalTeam = MunicipalTeam::query()->where('name', 'Gabinete Jurídico')->firstOrFail();
        $task = WorkTask::factory()->create([
            'type' => WorkTask::TYPE_COMPLAINT_REVIEW,
            'municipal_team_id' => $legalTeam->id,
            'assigned_user_id' => null,
            'status' => WorkTask::STATUS_PENDING,
        ]);

        $policy = new WorkTaskPolicy;

        $this->assertTrue($policy->view($legalUser, $task));
        $this->assertFalse($policy->view($supportUser, $task));
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
