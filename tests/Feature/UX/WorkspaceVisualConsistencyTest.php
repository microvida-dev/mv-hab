<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceVisualConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_workspace_cards_use_design_system_classes_and_keep_authorization(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('mv-page-shell', false)
            ->assertSee('mv-card-interactive', false)
            ->assertSee('Favoritos')
            ->assertSee('Recentes')
            ->assertSee('Atendimento')
            ->assertSee('Administração');
    }

    public function test_workspace_context_page_uses_consistent_cards_and_focus_styles(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'concursos'))
            ->assertOk()
            ->assertSee('Espaço de Trabalho Municipal')
            ->assertSee('mv-page-shell', false)
            ->assertSee('mv-card', false)
            ->assertSee('focus-visible:ring-2', false)
            ->assertSee('Ações rápidas');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
