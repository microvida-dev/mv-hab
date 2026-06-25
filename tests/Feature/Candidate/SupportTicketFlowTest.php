<?php

namespace Tests\Feature\Candidate;

use App\Enums\MessageVisibility;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_support_ticket_flow_keeps_internal_notes_private(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $this->seed(MunicipalTeamSeeder::class);
        $candidate = $this->userWithRole('candidate');
        $staff = $this->userWithRole('support_agent');

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => TicketCategory::Application->value,
                'subject' => 'Pedido sintético de apoio',
                'description' => 'Mensagem sintética com detalhe suficiente para validação.',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();

        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'visibility' => MessageVisibility::CandidateVisible->value,
        ]);

        $this->actingAs($staff)
            ->post(route('backoffice.support-ticket-messages.store', $ticket), [
                'message' => 'Nota interna sintética.',
                'visibility' => MessageVisibility::InternalOnly->value,
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('candidate.support-ticket-messages.store', $ticket), [
                'message' => 'Resposta sintética do candidato.',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Resposta sintética do candidato.')
            ->assertDontSee('Nota interna sintética.');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
