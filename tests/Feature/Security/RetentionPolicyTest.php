<?php

namespace Tests\Feature\Security;

use App\Enums\RetentionExecutionStatus;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Services\Rgpd\RetentionExecutionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_retention_simulation_approval_and_execution_are_audited(): void
    {
        $actor = $this->userWithRole('administrator');
        $policy = RetentionPolicy::factory()->create();
        $service = app(RetentionExecutionService::class);

        $execution = $service->simulate($policy, $actor);
        $this->assertSame(RetentionExecutionStatus::Simulation, $execution->status);

        $approved = $service->approve($execution, $actor);
        $this->assertSame(RetentionExecutionStatus::Approved, $approved->status);

        $completed = $service->run($approved, $actor);
        $this->assertSame(RetentionExecutionStatus::Completed, $completed->status);

        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_retention_simulated']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_retention_approved']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_retention_executed']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
