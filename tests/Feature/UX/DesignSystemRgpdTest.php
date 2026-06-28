<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_visual_components_do_not_expose_sensitive_timeline_details(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'title' => 'Evento operacional',
            'description' => 'Identificador ficticio 123456789 em storage/app/private/documento.pdf',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Evento operacional')
            ->assertDontSee('123456789')
            ->assertDontSee('storage/app/private')
            ->assertDontSee('documento.pdf');
    }

    public function test_empty_states_do_not_reveal_unauthorized_resource_existence(): void
    {
        $supportAgent = $this->userWithRole('support_agent');

        $this->actingAs($supportAgent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Atendimento')
            ->assertDontSee('Dossier documental privado')
            ->assertDontSee('storage/app/private');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
