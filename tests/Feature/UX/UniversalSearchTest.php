<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\Program;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class UniversalSearchTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
    }

    public function test_backoffice_user_searches_authorized_application_contest_workspace_and_task(): void
    {
        $administrator = $this->userWithRole('administrator');
        $program = Program::factory()->create(['municipality_id' => $this->municipality->id]);
        $application = Application::factory()->submitted()->create([
            'program_id' => $program->id,
            'application_number' => 'CAND-2026-UX05-001',
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
            'code' => 'CONC-UX05',
            'title' => 'Concurso UX Cinco',
        ]);
        $team = MunicipalTeam::factory()->create([
            'municipality_id' => $this->municipality->id,
        ]);
        WorkTask::factory()->assigned($administrator)->create([
            'municipal_team_id' => $team->id,
            'task_number' => 'WTK-UX05-001',
            'type' => WorkTask::TYPE_DOCUMENT_REVIEW,
            'created_by' => $administrator->id,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.search.index', ['q' => 'UX05']))
            ->assertOk()
            ->assertSee('Pesquisa Universal')
            ->assertSee('Candidaturas')
            ->assertSee('Candidatura '.$application->application_number)
            ->assertSee('Concurso UX Cinco')
            ->assertSee(route('backoffice.cases.contests.show', $contest), false)
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
            ->assertSee('for="dashboard-sidebar-search"', false)
            ->assertSee('aria-label="Pesquisar"', false)
            ->assertSee(route('backoffice.search.index'), false)
            ->assertDontSee('Preparado');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
