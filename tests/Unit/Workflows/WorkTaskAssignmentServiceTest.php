<?php

namespace Tests\Unit\Workflows;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskAssignmentService;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_workflow_assignment_service_exposes_required_competency_matrix(): void
    {
        $matrix = app(WorkTaskAssignmentService::class)->matrix();

        $this->assertSame(['Gabinete Técnico'], $matrix[WorkTask::TYPE_DOCUMENT_REVIEW]['teams']);
        $this->assertContains('legal_manager', $matrix[WorkTask::TYPE_COMPLAINT_REVIEW]['roles']);
        $this->assertContains('financial_manager', $matrix[WorkTask::TYPE_PAYMENT_REVIEW]['roles']);
        $this->assertContains('inspection_manager', $matrix[WorkTask::TYPE_INSPECTION_SCHEDULE]['roles']);
    }

    public function test_workflow_assignment_service_validates_profile_compatibility(): void
    {
        $service = app(WorkTaskAssignmentService::class);
        $legal = $this->userWithRole('legal_manager', 'Gabinete Jurídico');
        $support = $this->userWithRole('support_agent', 'Atendimento');

        $this->assertTrue($service->canUserHandleTaskType($legal, WorkTask::TYPE_COMPLAINT_REVIEW));
        $this->assertFalse($service->canUserHandleTaskType($support, WorkTask::TYPE_COMPLAINT_REVIEW));
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
