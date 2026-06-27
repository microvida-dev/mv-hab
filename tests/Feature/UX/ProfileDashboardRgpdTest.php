<?php

namespace Tests\Feature\UX;

use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileDashboardRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_dashboard_minimizes_sensitive_data_and_does_not_render_private_details(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        SupportTicket::factory()->create([
            'subject' => 'Contacto com detalhe reservado',
            'description' => 'email pessoa@example.test telefone 910000000 identificador fiscal ficticio 123456789 storage/app/private/documento.pdf',
            'status' => 'open',
        ]);

        $this->actingAs($supportAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tickets abertos')
            ->assertDontSee('123456789')
            ->assertDontSee('910000000')
            ->assertDontSee('pessoa@example.test')
            ->assertDontSee('storage/app/private');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
