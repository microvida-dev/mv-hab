<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantSupportEligibility;
use Tests\TestCase;

class Sprint52ACandidateExperienceTest extends TestCase
{
    use CreatesTenantSupportEligibility, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_legacy_candidate_visits_and_notification_preferences_are_disabled_by_default(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('candidate.visits.index'))
            ->assertNotFound();

        $this->actingAs($candidate)
            ->get(route('candidate.notification-preferences.edit'))
            ->assertNotFound();
    }

    public function test_support_is_refused_before_the_complete_tenant_lifecycle(): void
    {
        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.index'))
            ->assertForbidden();
    }

    public function test_support_is_available_after_contract_activation_and_key_handover(): void
    {
        $candidate = $this->candidate();
        $this->enableTenantSupportFor($candidate);

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.index'))
            ->assertOk()
            ->assertSee('apoio', false);
    }

    private function candidate(): User
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }
}
