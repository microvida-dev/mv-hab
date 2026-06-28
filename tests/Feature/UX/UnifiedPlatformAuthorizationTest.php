<?php

namespace Tests\Feature\UX;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedPlatformAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_does_not_receive_backoffice_unified_platform(): void
    {
        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('dashboard'))
            ->assertRedirect(route('candidate.dashboard'));
    }

    public function test_workspace_cards_are_limited_by_authorized_backoffice_profile(): void
    {
        $auditor = $this->userWithRole('auditor');

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Espaços de Trabalho')
            ->assertSee('Gestão')
            ->assertDontSee('Nova candidatura');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
