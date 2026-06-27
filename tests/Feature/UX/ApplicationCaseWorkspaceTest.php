<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationCaseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_case_shows_summary_progress_checklist_and_next_action(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee($application->application_number)
            ->assertSee('Progresso visual')
            ->assertSee('Recebida')
            ->assertSee('Documentação')
            ->assertSee('Documentos obrigatórios')
            ->assertSee('warning')
            ->assertSee('Validar documentos')
            ->assertSee('Abrir ação');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
