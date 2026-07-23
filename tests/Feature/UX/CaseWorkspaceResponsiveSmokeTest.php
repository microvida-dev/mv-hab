<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class CaseWorkspaceResponsiveSmokeTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_case_workspace_has_tablet_friendly_structure_without_global_menu_dependency(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        $this->assignApplicationMunicipality($technician, $application, FeatureKey::cases());

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('overflow-x-auto', false)
            ->assertSee('xl:grid-cols', false)
            ->assertSee('Pesquisar neste processo')
            ->assertSee('Painel do processo');
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
