<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MainDashboardVisualStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_keeps_phase_one_to_six_blocks_with_mature_visual_shell(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('mv-page-shell', false)
            ->assertSee('mv-card-interactive', false)
            ->assertSee('Pesquisa')
            ->assertSee('Espaços de Trabalho')
            ->assertSee('Ações rápidas')
            ->assertSee('Favoritos')
            ->assertSee('Recentes')
            ->assertSee('Centro de Trabalho');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
