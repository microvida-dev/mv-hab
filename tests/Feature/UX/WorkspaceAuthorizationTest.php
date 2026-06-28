<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_user_sees_only_authorized_workspaces(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($supportAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Atendimento')
            ->assertSee('Gestão')
            ->assertDontSee('Administração')
            ->assertDontSee('Património');
    }

    public function test_candidate_cannot_open_backoffice_workspace(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('workspaces.show', 'administracao'))
            ->assertForbidden();
    }

    public function test_context_sidebar_only_shows_current_workspace_modules(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'concursos'))
            ->assertOk()
            ->assertSee('Programas')
            ->assertSee('Elegibilidade')
            ->assertSee('Listas e alocações')
            ->assertDontSee('Tickets')
            ->assertDontSee('Manutenção');
    }

    public function test_atendimento_workspace_exposes_open_house_visit_management(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'atendimento'))
            ->assertOk()
            ->assertSee('Visitas abertas')
            ->assertSee('Horários de visita')
            ->assertSee('Visitas agendadas');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
