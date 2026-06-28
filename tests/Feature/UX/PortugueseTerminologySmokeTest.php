<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortugueseTerminologySmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_main_dashboard_uses_portuguese_operational_labels(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Painel Principal')
            ->assertSee('Indicadores do perfil')
            ->assertSee('Ações rápidas')
            ->assertSee('Favoritos')
            ->assertSee('Recentes')
            ->assertSee('Portal Público');
    }

    public function test_case_workspace_uses_portuguese_process_labels(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Espaço de Trabalho do Processo')
            ->assertSee('Próxima ação')
            ->assertSee('Histórico cronológico autorizado')
            ->assertSee('Checklist processual');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
