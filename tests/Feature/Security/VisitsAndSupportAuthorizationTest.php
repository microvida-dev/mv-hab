<?php

namespace Tests\Feature\Security;

use App\Enums\TicketCategory;
use App\Models\MunicipalTeam;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\MunicipalTeamSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitsAndSupportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_agent_cannot_access_financial_support_ticket(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $support = $this->userWithRole('support_agent', 'Atendimento');
        $finance = $this->userWithRole('financial_manager', 'Gabinete Financeiro');
        $ticket = SupportTicket::factory()->create([
            'user_id' => $candidate->id,
            'category' => TicketCategory::Payment->value,
            'subject' => 'Pedido financeiro sintético',
        ]);

        $this->actingAs($support)
            ->get(route('backoffice.support-tickets.show', $ticket))
            ->assertForbidden();

        $this->actingAs($support)
            ->get(route('backoffice.support-tickets.index'))
            ->assertOk()
            ->assertDontSee('Pedido financeiro sintético');

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($finance)
            ->get(route('backoffice.support-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pedido financeiro sintético');
    }

    public function test_auditor_can_view_sensitive_support_ticket_without_mutating_it(): void
    {
        $this->seedAccess();
        $candidate = $this->userWithRole('candidate');
        $auditor = $this->userWithRole('auditor', 'Auditoria');
        $ticket = SupportTicket::factory()->create([
            'user_id' => $candidate->id,
            'category' => TicketCategory::Legal->value,
        ]);

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($auditor)
            ->get(route('backoffice.support-tickets.show', $ticket))
            ->assertOk();

        $this->withSession(['mfa.verified_at' => now()])
            ->actingAs($auditor)
            ->post(route('backoffice.support-tickets.status', $ticket), [
                'status' => 'resolved',
                'message' => 'Tentativa sintética.',
            ])
            ->assertForbidden();
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
