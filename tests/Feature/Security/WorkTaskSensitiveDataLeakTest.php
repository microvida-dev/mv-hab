<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskSlaPolicy;
use App\Services\Workflows\WorkTaskCreationService;
use App\Services\Workflows\WorkTaskSlaService;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkTaskSensitiveDataLeakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_work_task_metadata_minimizes_sensitive_values_and_sla_changes_are_audited(): void
    {
        $actor = $this->userWithRole('administrator');
        $task = app(WorkTaskCreationService::class)->createFromSource(
            type: WorkTask::TYPE_RGPD_REQUEST,
            actor: $actor,
            source: 'rgpd_request_created',
            metadata: [
                'nif' => '123456789',
                'storage_path' => 'private/doc.pdf',
                'email' => 'titular@example.test',
                'safe_reference' => 'RGPD-REF-1',
            ],
        );

        $this->assertArrayNotHasKey('nif', $task->metadata ?? []);
        $this->assertArrayNotHasKey('storage_path', $task->metadata ?? []);
        $this->assertArrayNotHasKey('email', $task->metadata ?? []);
        $this->assertSame('RGPD-REF-1', $task->metadata['safe_reference'] ?? null);

        $policy = WorkTaskSlaPolicy::factory()->create([
            'type' => WorkTask::TYPE_RGPD_REQUEST,
            'business_days' => 15,
        ]);
        app(WorkTaskSlaService::class)->auditPolicyChanged($policy, $actor, 15, 10);

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'work_task_sla_changed',
            'auditable_id' => $policy->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
