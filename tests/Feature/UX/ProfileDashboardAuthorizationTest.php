<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_financial_widgets_are_visible_only_to_authorized_profile(): void
    {
        $financialManager = $this->userWithRole('financial_manager');
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($financialManager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rendas pendentes')
            ->assertSee('Pagamentos por validar')
            ->assertSee('Controlo financeiro');

        $this->actingAs($supportAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tickets abertos')
            ->assertDontSee('Rendas pendentes')
            ->assertDontSee('Pagamentos por validar')
            ->assertDontSee('Controlo financeiro');
    }

    public function test_auditor_dashboard_is_read_only_and_has_no_mutating_quick_actions(): void
    {
        $auditor = $this->userWithRole('auditor');

        $this->actingAs($auditor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Auditoria')
            ->assertSee('Auditoria em leitura')
            ->assertSee('Ver auditoria')
            ->assertSee('Acessos sensíveis')
            ->assertDontSee('Gerir utilizadores')
            ->assertDontSee('Rever documentos')
            ->assertDontSee('Ver pagamentos');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
