<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use App\Models\Contest;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniversalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_user_searches_authorized_application_contest_workspace_and_task(): void
    {
        $administrator = $this->userWithRole('administrator');
        $application = Application::factory()->submitted()->create([
            'application_number' => 'CAND-2026-UX05-001',
        ]);
        Contest::factory()->create([
            'code' => 'CONC-UX05',
            'title' => 'Concurso UX Cinco',
        ]);
        WorkTask::factory()->assigned($administrator)->create([
            'task_number' => 'WTK-UX05-001',
            'type' => WorkTask::TYPE_DOCUMENT_REVIEW,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'UX05']))
            ->assertOk()
            ->assertSee('Pesquisa Universal')
            ->assertSee('Candidaturas')
            ->assertSee('Candidatura '.$application->application_number)
            ->assertSee('Concurso UX Cinco')
            ->assertSee('WTK-UX05-001')
            ->assertSee('Centro de Comandos');
    }

    public function test_dashboard_uses_functional_search_component_instead_of_prepared_state(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pesquisar')
            ->assertSee('Centro de Comandos')
            ->assertSee(route('backoffice.search.index'), false)
            ->assertDontSee('Preparado');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
