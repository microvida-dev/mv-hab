<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_quick_actions_change_with_profile(): void
    {
        $jury = $this->userWithRole('jury');
        $maintenanceManager = $this->userWithRole('maintenance_manager');

        $this->actingAs($jury)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Classificar processos')
            ->assertSee('Ver listas')
            ->assertSee('Ver reclamações')
            ->assertDontSee('Pedidos urgentes');

        $this->actingAs($maintenanceManager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pedidos urgentes')
            ->assertSee('Ver vistorias')
            ->assertSee('Tarefas vencidas')
            ->assertDontSee('Classificar processos');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
