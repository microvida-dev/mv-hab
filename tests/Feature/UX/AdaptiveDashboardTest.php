<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_municipal_technician_receives_technical_dashboard_focus(): void
    {
        $user = $this->userWithRole('municipal_technician');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Operação técnica')
            ->assertSee('Foco técnico')
            ->assertSee('Documentos, candidaturas, aperfeiçoamentos e SLA operacional.')
            ->assertSee('Ação principal');
    }

    public function test_auditor_receives_audit_dashboard_focus(): void
    {
        $user = $this->userWithRole('auditor');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('Rastreabilidade e conformidade')
            ->assertSee('Consulte eventos, acessos sensíveis, pedidos RGPD e relatórios autorizados.');
    }

    public function test_administrator_receives_global_operation_focus(): void
    {
        $user = $this->userWithRole('administrator');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administração municipal')
            ->assertSee('Visão global da operação')
            ->assertSee('segurança, equipas, riscos');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
