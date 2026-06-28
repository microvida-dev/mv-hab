<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandPaletteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_command_palette_shows_only_authorized_non_destructive_commands(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.commands'))
            ->assertOk()
            ->assertSee('Centro de Comandos')
            ->assertSee('Abrir Painel Principal')
            ->assertSee('Ver minhas tarefas')
            ->assertSee('Abrir auditoria')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Apagar');
    }

    public function test_command_without_permission_is_hidden(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($supportAgent)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.commands'))
            ->assertOk()
            ->assertSee('Ver minhas tarefas')
            ->assertDontSee('Abrir auditoria')
            ->assertDontSee('Abrir relatórios');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
