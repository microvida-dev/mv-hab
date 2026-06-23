<?php

namespace Tests\Feature\Candidate;

use App\Enums\RegistrationRenewalStatus;
use App\Models\AdhesionRegistration;
use App\Models\RegistrationRenewal;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_starts_updates_and_submits_registration_renewal(): void
    {
        $candidate = $this->userWithRole('candidate');
        AdhesionRegistration::factory()->registered()->for($candidate)->create();

        $this->actingAs($candidate)
            ->post(route('candidate.registration-renewals.store'), ['reason' => 'candidate_update'])
            ->assertRedirect();

        $renewal = RegistrationRenewal::query()->firstOrFail();

        $this->actingAs($candidate)
            ->patch(route('candidate.registration-renewals.update', $renewal), [
                'phone' => '210000000',
                'city' => 'Alcanena',
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->post(route('candidate.registration-renewals.submit', $renewal), [
                'confirm_data_current' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(RegistrationRenewalStatus::Completed, $renewal->fresh()->status);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
