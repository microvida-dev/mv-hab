<?php

namespace Tests\Feature\Security;

use App\Models\ApplicationPrefill;
use App\Models\RegistrationRenewal;
use App\Models\SimulationSession;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulatorPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_authenticated_simulation_is_not_available_through_public_result_route(): void
    {
        $candidate = $this->userWithRole('candidate');
        $session = SimulationSession::factory()->forCandidate($candidate)->create();

        $this->get(route('public.simulator.result', ['uuid' => $session->uuid]))
            ->assertNotFound();
    }

    public function test_candidate_cannot_access_other_candidate_prefill_or_renewal(): void
    {
        $owner = $this->userWithRole('candidate');
        $other = $this->userWithRole('candidate');

        $prefill = ApplicationPrefill::factory()->create(['user_id' => $owner->id]);
        $renewal = RegistrationRenewal::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('candidate.application-prefills.show', $prefill))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('candidate.registration-renewals.show', $renewal))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
