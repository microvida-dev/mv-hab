<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_authorized_technician_opens_application_case_workspace(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Espaço de Trabalho do Processo')
            ->assertSee('Candidatura')
            ->assertSee('Resumo')
            ->assertSee('Cronologia')
            ->assertSee('Documentos')
            ->assertSee('Checklist processual')
            ->assertSee('Painel do processo');
    }

    public function test_dashboard_and_workspace_navigation_remain_available(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Painel Principal')
            ->assertSee('Indicadores do perfil');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'atendimento'))
            ->assertOk()
            ->assertSee('Atendimento')
            ->assertSee('Candidaturas');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
