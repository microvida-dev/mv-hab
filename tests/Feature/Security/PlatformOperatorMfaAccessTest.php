<?php

namespace Tests\Feature\Security;

use App\Enums\PlatformOperatorStatus;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Policies\MfaDevicePolicy;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorMfaAccessTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_active_platform_operator_can_manage_own_mfa(): void
    {
        $user = $this->platformUser([
            'platform_operators.view',
            'security.manage_own_mfa',
        ]);

        $ownDevice = $user->mfaDevices()->firstOrFail();

        $otherUser = User::factory()->create([
            'status' => 'active',
        ]);

        $foreignDevice = MfaDevice::factory()
            ->confirmed()
            ->for($otherUser)
            ->create();

        $policy = app(MfaDevicePolicy::class);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $ownDevice));
        $this->assertTrue($policy->update($user, $ownDevice));
        $this->assertFalse($policy->view($user, $foreignDevice));

        $this->actingAs($user)
            ->get(route('backoffice.security.mfa.index'))
            ->assertOk();
    }

    public function test_municipal_user_with_permission_remains_authorized(): void
    {
        $municipality = Municipality::factory()->create();

        $user = $this->platformUser(
            permissions: ['security.manage_own_mfa'],
            assigned: false,
            municipalityId: $municipality->id,
        );

        $this->assertTrue(
            app(MfaDevicePolicy::class)->viewAny($user),
        );

        $this->actingAs($user)
            ->get(route('backoffice.security.mfa.index'))
            ->assertOk();
    }

    public function test_unassigned_user_without_municipality_is_denied(): void
    {
        $user = $this->platformUser(
            permissions: [
                'platform_operators.view',
                'security.manage_own_mfa',
            ],
            assigned: false,
        );

        $this->assertFalse(
            app(MfaDevicePolicy::class)->viewAny($user),
        );

        $this->actingAs($user)
            ->get(route('backoffice.security.mfa.index'))
            ->assertForbidden();
    }

    public function test_revoked_platform_assignment_is_denied(): void
    {
        $user = $this->platformUser([
            'platform_operators.view',
            'security.manage_own_mfa',
        ]);

        PlatformOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->update([
                'status' => PlatformOperatorStatus::Revoked->value,
                'revoked_by' => $user->id,
                'revoked_at' => now(),
                'revoke_justification' => 'Revogação dirigida de teste.',
            ]);

        $user->refresh();

        $this->assertFalse(
            app(MfaDevicePolicy::class)->viewAny($user),
        );

        $this->actingAs($user)
            ->get(route('backoffice.security.mfa.index'))
            ->assertForbidden();
    }

    public function test_platform_operator_without_mfa_permission_is_denied(): void
    {
        $user = $this->platformUser([
            'platform_operators.view',
        ]);

        $this->assertFalse(
            app(MfaDevicePolicy::class)->viewAny($user),
        );

        $this->actingAs($user)
            ->get(route('backoffice.security.mfa.index'))
            ->assertForbidden();
    }

    public function test_inactive_platform_operator_is_denied(): void
    {
        $user = $this->platformUser([
            'platform_operators.view',
            'security.manage_own_mfa',
        ]);

        $user->forceFill([
            'status' => 'inactive',
        ])->save();

        $this->assertFalse(
            app(MfaDevicePolicy::class)->viewAny($user->refresh()),
        );
    }
}
