<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MfaEnforcementBackofficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_sensitive_municipal_profiles_require_mfa(): void
    {
        $mfa = app(MfaEnforcementService::class);

        foreach (['administrator', 'municipal_technician', 'jury', 'legal_manager', 'financial_manager', 'housing_manager', 'inspection_manager', 'auditor'] as $role) {
            $this->assertTrue($mfa->requiresMfa($this->userWithRole($role)), $role.' should require MFA');
        }

        $this->assertFalse($mfa->requiresMfa($this->userWithRole('support_agent')));
    }

    public function test_administrator_can_force_mfa_for_user(): void
    {
        $administrator = $this->userWithRole('administrator');
        $target = $this->userWithRole('support_agent');

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.force-mfa', $target), [
                'justification' => 'Obrigatoriedade MFA QA30.',
            ])
            ->assertRedirect();

        $this->assertTrue($target->refresh()->mfa_required);
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'user_mfa_enforced',
            'target_user_id' => $target->id,
        ]);
    }

    public function test_forced_mfa_blocks_sensitive_backoffice_without_verified_session(): void
    {
        $administrator = $this->userWithRole('administrator', ['mfa_required' => true]);

        $this
            ->actingAs($administrator)
            ->get(route('backoffice.security.dashboard'))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => $role.'-mfa-qa30-'.fake()->unique()->numerify('####').'@example.test',
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
