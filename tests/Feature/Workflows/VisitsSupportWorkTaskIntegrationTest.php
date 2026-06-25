<?php

namespace Tests\Feature\Workflows;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\MunicipalTeam;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsSupportWorkTaskIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_ticket_creates_competency_work_task_with_minimized_metadata(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $support = $this->userWithRole('support_agent', 'Atendimento');

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Application->value,
                'priority' => TicketPriority::High->value,
                'subject' => 'Pedido sintético de apoio',
                'description' => 'Mensagem sintética com detalhe suficiente para validação.',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $task = WorkTask::query()->where('related_type', $ticket->getMorphClass())->where('related_id', $ticket->id)->firstOrFail();

        $this->assertSame(WorkTask::TYPE_SUPPORT_TICKET, $task->type);
        $this->assertSame(WorkTask::PRIORITY_HIGH, $task->priority);
        $this->assertSame('Atendimento', $task->municipalTeam?->name);
        $this->assertSame($support->id, $task->assigned_user_id);
        $this->assertSame('support_ticket:'.$ticket->id, $task->source);
        $this->assertArrayNotHasKey('subject', $task->metadata ?? []);
        $this->assertArrayNotHasKey('description', $task->metadata ?? []);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'work_task_created']);
    }

    public function test_rgpd_and_payment_tickets_are_routed_to_competent_teams(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $auditor = $this->userWithRole('auditor', 'Auditoria');
        $finance = $this->userWithRole('financial_manager', 'Gabinete Financeiro');

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Rgpd->value,
                'subject' => 'Pedido sintético RGPD',
                'description' => 'Mensagem sintética com detalhe suficiente para validação.',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Payment->value,
                'subject' => 'Pedido sintético financeiro',
                'description' => 'Mensagem sintética com detalhe suficiente para validação.',
            ])
            ->assertRedirect();

        $rgpdTask = WorkTask::query()->where('type', WorkTask::TYPE_RGPD_REQUEST)->firstOrFail();
        $paymentTask = WorkTask::query()->where('type', WorkTask::TYPE_PAYMENT_REVIEW)->firstOrFail();

        $this->assertSame('Auditoria', $rgpdTask->municipalTeam?->name);
        $this->assertSame($auditor->id, $rgpdTask->assigned_user_id);
        $this->assertSame('Gabinete Financeiro', $paymentTask->municipalTeam?->name);
        $this->assertSame($finance->id, $paymentTask->assigned_user_id);
    }

    private function seedAccess(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
    }

    private function userWithRole(string $role, ?string $teamName = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        if ($teamName !== null) {
            MunicipalTeam::query()->where('name', $teamName)->firstOrFail()->members()->syncWithoutDetaching([
                $user->id => ['joined_at' => now(), 'role_in_team' => $teamName],
            ]);
        }

        return $user;
    }
}
