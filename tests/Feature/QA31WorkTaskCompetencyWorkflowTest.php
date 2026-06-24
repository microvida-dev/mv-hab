<?php

namespace Tests\Feature;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskCreationService;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QA31WorkTaskCompetencyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    public function test_qa31_creates_document_review_task_idempotently_with_history_and_audit(): void
    {
        $actor = $this->userWithRole('administrator');
        $technician = $this->userWithRole('municipal_technician', 'Gabinete Técnico');

        $service = app(WorkTaskCreationService::class);

        $first = $service->createFromSource(
            type: WorkTask::TYPE_DOCUMENT_REVIEW,
            actor: $actor,
            source: 'document_submitted',
            metadata: ['document_id' => 123, 'nif' => '123456789', 'storage_path' => '/private/path'],
        );
        $second = $service->createFromSource(
            type: WorkTask::TYPE_DOCUMENT_REVIEW,
            actor: $actor,
            source: 'document_submitted',
            metadata: ['document_id' => 123],
        );

        $this->assertTrue($first->is($second));
        $this->assertSame('Gabinete Técnico', $first->municipalTeam?->name);
        $this->assertSame($technician->id, $first->assigned_user_id);
        $this->assertArrayNotHasKey('nif', $first->metadata ?? []);
        $this->assertArrayNotHasKey('storage_path', $first->metadata ?? []);
        $this->assertDatabaseCount('work_tasks', 1);
        $this->assertDatabaseHas('work_task_histories', ['work_task_id' => $first->id, 'event_code' => 'work_task_created']);
        $this->assertDatabaseHas('work_task_histories', ['work_task_id' => $first->id, 'event_code' => 'work_task_assigned']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'work_task_created']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'work_task_assigned']);
    }

    public function test_qa31_routes_core_task_types_to_competent_teams_and_profiles(): void
    {
        $actor = $this->userWithRole('administrator');
        $legal = $this->userWithRole('legal_manager', 'Gabinete Jurídico');
        $finance = $this->userWithRole('financial_manager', 'Gabinete Financeiro');
        $maintenance = $this->userWithRole('maintenance_manager', 'Manutenção');

        $service = app(WorkTaskCreationService::class);

        $complaint = $service->createFromSource(WorkTask::TYPE_COMPLAINT_REVIEW, actor: $actor, source: 'complaint_submitted');
        $payment = $service->createFromSource(WorkTask::TYPE_PAYMENT_REVIEW, actor: $actor, source: 'payment_registered');
        $triage = $service->createFromSource(WorkTask::TYPE_MAINTENANCE_TRIAGE, actor: $actor, source: 'maintenance_request_created');

        $this->assertSame('Gabinete Jurídico', $complaint->municipalTeam?->name);
        $this->assertSame($legal->id, $complaint->assigned_user_id);
        $this->assertSame('Gabinete Financeiro', $payment->municipalTeam?->name);
        $this->assertSame($finance->id, $payment->assigned_user_id);
        $this->assertSame('Manutenção', $triage->municipalTeam?->name);
        $this->assertSame($maintenance->id, $triage->assigned_user_id);
    }

    private function userWithRole(string $role, ?string $teamName = null): User
    {
        $user = User::factory()->create([
            'email' => $role.'-qa31-'.fake()->unique()->numerify('####').'@example.test',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        if ($teamName !== null) {
            $team = MunicipalTeam::query()->where('name', $teamName)->firstOrFail();
            $team->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $role],
            ]);
        }

        return $user;
    }
}
