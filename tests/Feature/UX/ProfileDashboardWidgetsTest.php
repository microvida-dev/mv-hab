<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\DocumentSubmission;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ProfileDashboardWidgetsTest extends TestCase
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

    public function test_dashboard_renders_profile_widgets_metrics_and_deadlines(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $team = MunicipalTeam::factory()->create(['name' => 'Gabinete Técnico']);
        $technician->municipalTeams()->attach($team->id, ['joined_at' => now()]);

        WorkTask::factory()
            ->assigned($technician)
            ->create([
                'municipal_team_id' => $team->id,
                'due_at' => now()->subDay(),
            ]);

        $candidate = User::factory()->create(['municipality_id' => $this->municipality->id]);
        DocumentSubmission::factory()->create([
            'user_id' => $candidate->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($technician)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Revisão técnica')
            ->assertSee('Tarefas atribuídas')
            ->assertSee('Tarefas da equipa')
            ->assertSee('Tarefas vencidas')
            ->assertSee('Documentos pendentes')
            ->assertSee('Abrir revisão')
            ->assertSee('Prioridade Alta')
            ->assertSee('SLA');
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
