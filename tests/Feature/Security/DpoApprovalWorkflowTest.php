<?php

namespace Tests\Feature\Security;

use App\Models\AnonymizationRequest;
use App\Models\RgpdApproval;
use App\Models\User;
use App\Services\Rgpd\DpoApprovalService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DpoApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dpo_approval_workflow_records_request_approval_and_execution(): void
    {
        $actor = $this->userWithRole('administrator');
        $approvable = AnonymizationRequest::factory()->create();
        $service = app(DpoApprovalService::class);

        $approval = $service->request(
            approvable: $approvable,
            actor: $actor,
            flowType: 'rgpd_anonymization',
            justification: 'Pedido DPO QA32 justificado.',
            metadata: ['scope' => ['user.profile']],
        );

        $this->assertSame(RgpdApproval::STATUS_PENDING_DPO_APPROVAL, $approval->status);

        $approved = $service->approve($approval, $actor, 'Aprovado em teste QA32.');
        $this->assertSame(RgpdApproval::STATUS_APPROVED, $approved->status);

        $executed = $service->markExecuted($approved, $actor);
        $this->assertSame(RgpdApproval::STATUS_EXECUTED, $executed->status);

        $this->assertDatabaseHas('audit_events', ['event_code' => 'rgpd_anonymization_requested']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'dpo_approval_approved']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'dpo_approval_executed']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
