<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalUnifiedPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_main_dashboard_uses_unified_municipal_language_and_structure(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Centro de Operações Municipal da Habitação')
            ->assertSee('Painel Principal')
            ->assertSee('Espaços de Trabalho')
            ->assertSee('Centro de Comandos')
            ->assertSee('Centro de Trabalho')
            ->assertSee('Caixa de Entrada Municipal')
            ->assertDontSee('Workspaces')
            ->assertDontSee('Inbox Municipal')
            ->assertDontSee('My Work');
    }

    public function test_workspace_page_keeps_contextual_navigation_in_portuguese(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'gestao'))
            ->assertOk()
            ->assertSee('Espaço de Trabalho Municipal')
            ->assertSee('Painel')
            ->assertSee('Tarefas')
            ->assertSee('Painel executivo')
            ->assertDontSee('Workspace municipal')
            ->assertDontSee('Work Tasks');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
