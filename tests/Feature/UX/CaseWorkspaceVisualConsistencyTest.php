<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class CaseWorkspaceVisualConsistencyTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_case_workspace_uses_design_system_structure(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        $this->assignApplicationMunicipality($technician, $application, FeatureKey::cases());

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Espaço de Trabalho do Processo')
            ->assertSee('Painel do processo')
            ->assertSee('Progresso visual')
            ->assertSee('Checklist processual')
            ->assertSee('mv-card', false)
            ->assertSee('role="tab"', false)
            ->assertSee('aria-label="Separadores do processo"', false);
    }

    private function userWithRole(string $role): User
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::cases());
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
