<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_user_enters_workspace_dashboard_without_global_side_navigation(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Painel Principal')
            ->assertSee('Espaços de Trabalho')
            ->assertSee('Atendimento')
            ->assertSee('Concursos')
            ->assertSee('Património')
            ->assertSee('Gestão')
            ->assertSee('Administração')
            ->assertSee('Pesquisar')
            ->assertSee('Favoritos')
            ->assertSee('Recentes')
            ->assertDontSee('Caixa de trabalho');
    }

    public function test_candidate_keeps_candidate_experience_and_is_redirected_from_municipal_dashboard(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertRedirect(route('candidate.dashboard'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
