<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_administrator_sees_transversal_profile_dashboard(): void
    {
        $administrator = $this->userWithRole('administrator', 'Ana Administradora');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Centro de Operações Municipal da Habitação')
            ->assertSee('Painel Principal')
            ->assertSee('Bom trabalho, Ana')
            ->assertSee('Administração municipal')
            ->assertSee('Utilizadores ativos')
            ->assertSee('Equipas ativas')
            ->assertSee('Alertas de segurança')
            ->assertSee('Espaços de Trabalho');
    }

    public function test_municipal_technician_sees_operational_review_dashboard(): void
    {
        $technician = $this->userWithRole('municipal_technician', 'Tiago Técnico');

        $this->actingAs($technician)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bom trabalho, Tiago')
            ->assertSee('Técnico municipal')
            ->assertSee('Candidaturas pendentes')
            ->assertSee('Documentos pendentes')
            ->assertSee('Tarefas atribuídas')
            ->assertSee('Revisão técnica');
    }

    public function test_candidate_keeps_candidate_dashboard_and_does_not_receive_backoffice_widgets(): void
    {
        $candidate = $this->userWithRole('candidate', 'Carla Candidata');

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertRedirect(route('candidate.dashboard'));
    }

    private function userWithRole(string $role, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
