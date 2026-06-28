<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardVisualConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_profile_dashboard_keeps_municipal_layout_and_profile_widgets(): void
    {
        $technician = $this->userWithRole('municipal_technician');

        $this->actingAs($technician)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Centro de Operações Municipal da Habitação')
            ->assertSee('Painel Principal')
            ->assertSee('Indicadores do perfil')
            ->assertSee('Ações rápidas')
            ->assertSee('Alertas e prazos')
            ->assertSee('mv-badge', false)
            ->assertSee('mv-card', false);
    }

    public function test_candidate_is_not_moved_into_backoffice_design_system_dashboard(): void
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
