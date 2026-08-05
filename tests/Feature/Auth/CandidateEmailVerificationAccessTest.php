<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CandidateEmailVerificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_candidate_and_tenant_routes_require_verified_email(): void
    {
        $candidateRoutes = 0;
        $tenantRoutes = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name)) {
                continue;
            }

            if (str_starts_with($name, 'candidate.')) {
                $candidateRoutes++;
                $this->assertContains('verified', $route->gatherMiddleware(), $name);

                continue;
            }

            if (str_starts_with($name, 'tenant.')) {
                $tenantRoutes++;
                $this->assertContains('verified', $route->gatherMiddleware(), $name);
            }
        }

        $this->assertSame(198, $candidateRoutes);
        $this->assertSame(18, $tenantRoutes);
    }

    public function test_unverified_candidate_is_redirected_from_candidate_area(): void
    {
        $candidate = $this->candidate(verified: false);

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_candidate_mutation_is_blocked_before_validation(): void
    {
        $candidate = $this->candidate(verified: false);

        $this->actingAs($candidate)
            ->post(route('candidate.registration.store'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_candidate_is_redirected_from_tenant_area(): void
    {
        $candidate = $this->candidate(verified: false);

        $this->actingAs($candidate)
            ->get(route('tenant.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_candidate_is_redirected_from_central_dashboard(): void
    {
        $candidate = $this->candidate(verified: false);

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_candidate_can_access_verification_and_profile_pages(): void
    {
        $candidate = $this->candidate(verified: false);

        $this->actingAs($candidate)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee($candidate->email);

        $this->actingAs($candidate)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_verified_candidate_can_access_candidate_dashboard(): void
    {
        $candidate = $this->candidate(verified: true);

        $this->actingAs($candidate)
            ->get(route('candidate.dashboard'))
            ->assertOk();
    }

    private function candidate(bool $verified): User
    {
        $this->seed(SystemAccessSeeder::class);

        $attributes = [
            'status' => 'active',
            'deactivated_at' => null,
        ];

        $candidate = $verified
            ? User::factory()->create($attributes)
            : User::factory()->unverified()->create($attributes);

        $candidate->assignRole('candidate');

        return $candidate;
    }
}
