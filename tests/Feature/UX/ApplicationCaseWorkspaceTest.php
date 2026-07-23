<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ApplicationCaseWorkspaceTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
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
        $this->assignApplicationMunicipality($technician, $application, FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
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
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
