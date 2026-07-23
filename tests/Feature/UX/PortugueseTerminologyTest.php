<?php

namespace Tests\Feature\UX;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class PortugueseTerminologyTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_critical_english_terms_do_not_appear_on_primary_ux_pages(): void
    {
        $administrator = $this->userWithRole('administrator');
        $application = Application::factory()->submitted()->create();
        $this->assignApplicationMunicipality($administrator, $application, FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Workspaces')
            ->assertDontSee('Inbox Municipal')
            ->assertDontSee('My Work');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.cases.applications.show', $application))
            ->assertOk()
            ->assertSee('Cronologia')
            ->assertDontSee('Timeline')
            ->assertDontSee('Work Task');
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
