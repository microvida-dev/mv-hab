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

class CaseChecklistTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_checklist_marks_pending_documents_and_missing_eligibility(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $application = Application::factory()->submitted()->create();
        $this->assignApplicationMunicipality($technician, $application, FeatureKey::cases());
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Documentos obrigatórios')
            ->assertSee('Existem documentos pendentes.')
            ->assertSee('Elegibilidade')
            ->assertSee('Verificação formal de elegibilidade.');
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
