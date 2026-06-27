<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseWorkspaceRgpdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_case_workspace_does_not_expose_sensitive_identifiers_or_private_paths(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'title' => 'Evento minimizado',
            'description' => 'Contacto fictício com identificador 123456789 e storage/app/private/ficheiro.pdf',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Evento minimizado')
            ->assertDontSee('123456789')
            ->assertDontSee('storage/app/private')
            ->assertDontSee('email')
            ->assertDontSee('telefone');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
