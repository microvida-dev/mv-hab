<?php

namespace Tests\Feature\Candidate;

use App\Models\SimulationSession;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_simulation_routes_are_protected_and_store_history(): void
    {
        $this->get(route('candidate.simulations.index'))->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('candidate.simulations.create'))
            ->assertOk();

        $this->actingAs($candidate)
            ->post(route('candidate.simulations.store'), [
                'household_members_count' => 1,
                'adults_count' => 1,
                'dependents_count' => 0,
                'monthly_income' => 1000,
                'housing_status' => 'rented',
                'privacy_notice_accepted' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('simulation_sessions', [
            'user_id' => $candidate->id,
        ]);
    }

    public function test_candidate_cannot_view_another_candidate_simulation(): void
    {
        $owner = $this->userWithRole('candidate');
        $other = $this->userWithRole('candidate');
        $session = SimulationSession::factory()->forCandidate($owner)->create();

        $this->actingAs($other)
            ->get(route('candidate.simulations.show', $session))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
