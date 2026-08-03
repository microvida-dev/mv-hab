<?php

namespace Tests\Feature\Security;

use App\Enums\PlatformOperatorStatus;
use App\Models\MfaDevice;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Security\MfaDeviceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorMfaActionsTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_global_operator_can_verify_valid_totp_and_invalid_code_reaches_controller(): void
    {
        $user = $this->authorizedPlatformOperator();
        $device = $user->mfaDevices()->firstOrFail();
        $service = app(MfaDeviceService::class);

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.verify'), [
                'code' => $service->totp($device->secret_encrypted),
            ])
            ->assertRedirect(route('backoffice.security.dashboard'))
            ->assertSessionHas('status', 'Sessão MFA validada.')
            ->assertSessionHas('mfa.verified_at');

        session()->forget('mfa.verified_at');

        $this->actingAs($user)
            ->from(route('backoffice.security.mfa.index'))
            ->post(route('backoffice.security.mfa.verify'), [
                'code' => '000000',
            ])
            ->assertRedirect(route('backoffice.security.mfa.index'))
            ->assertSessionHasErrors([
                'code' => 'Código MFA inválido.',
            ])
            ->assertSessionMissing('mfa.verified_at');
    }

    public function test_global_operator_can_enable_device_and_regenerate_recovery_codes(): void
    {
        $user = $this->authorizedPlatformOperator();

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.enable'), [
                'name' => 'Dispositivo RC3',
            ])
            ->assertOk()
            ->assertViewIs('backoffice.security.mfa.index')
            ->assertViewHas('setupDevice')
            ->assertViewHas('recoveryCodes');

        $this->assertDatabaseHas('mfa_devices', [
            'user_id' => $user->id,
            'name' => 'Dispositivo RC3',
            'confirmed_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.recovery-codes.regenerate'))
            ->assertOk()
            ->assertViewIs('backoffice.security.mfa.index')
            ->assertViewHas(
                'recoveryCodes',
                fn (array $codes): bool => count($codes) === 8,
            );

        $this->assertDatabaseCount('mfa_recovery_codes', 8);
    }

    public function test_global_operator_can_confirm_and_disable_own_device(): void
    {
        $user = $this->authorizedPlatformOperator();
        $device = MfaDevice::factory()->for($user)->create();
        $service = app(MfaDeviceService::class);

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.confirm', $device), [
                'code' => $service->totp($device->secret_encrypted),
            ])
            ->assertRedirect(route('backoffice.security.dashboard'))
            ->assertSessionHas('status', 'MFA confirmado.');

        $this->assertNotNull($device->fresh()?->confirmed_at);

        $this->actingAs($user)
            ->from(route('backoffice.security.mfa.index'))
            ->post(route('backoffice.security.mfa.disable', $device))
            ->assertRedirect(route('backoffice.security.mfa.index'))
            ->assertSessionHas('status', 'Dispositivo MFA desativado.');

        $this->assertNotNull($device->fresh()?->disabled_at);
    }

    public function test_global_operator_cannot_confirm_or_disable_foreign_device(): void
    {
        $user = $this->authorizedPlatformOperator();
        $otherUser = User::factory()->create(['status' => 'active']);
        $foreignDevice = MfaDevice::factory()->for($otherUser)->create();
        $service = app(MfaDeviceService::class);

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.confirm', $foreignDevice), [
                'code' => $service->totp($foreignDevice->secret_encrypted),
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.disable', $foreignDevice))
            ->assertForbidden();

        $foreignDevice->refresh();

        $this->assertNull($foreignDevice->confirmed_at);
        $this->assertNull($foreignDevice->disabled_at);
    }

    public function test_municipal_user_with_permission_can_still_verify_totp(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformUser(
            permissions: ['security.manage_own_mfa'],
            assigned: false,
            municipalityId: $municipality->id,
        );
        $device = $user->mfaDevices()->firstOrFail();

        $this->actingAs($user)
            ->post(route('backoffice.security.mfa.verify'), [
                'code' => app(MfaDeviceService::class)
                    ->totp($device->secret_encrypted),
            ])
            ->assertRedirect(route('backoffice.security.dashboard'))
            ->assertSessionHas('mfa.verified_at');
    }

    public function test_mfa_actions_remain_fail_closed_without_valid_scope_permission_or_status(): void
    {
        $unassigned = $this->platformUser(
            permissions: [
                'platform_operators.view',
                'security.manage_own_mfa',
            ],
            assigned: false,
        );

        $withoutPermission = $this->platformUser([
            'platform_operators.view',
        ]);

        $revoked = $this->authorizedPlatformOperator();
        PlatformOperatorAssignment::query()
            ->where('user_id', $revoked->id)
            ->update([
                'status' => PlatformOperatorStatus::Revoked->value,
                'revoked_by' => $revoked->id,
                'revoked_at' => now(),
                'revoke_justification' => 'Revogação dirigida RC3.',
            ]);

        $inactive = $this->authorizedPlatformOperator();
        $inactive->forceFill(['status' => 'inactive'])->save();

        foreach ([$unassigned, $withoutPermission, $revoked, $inactive] as $user) {
            $this->actingAs($user->refresh())
                ->post(route('backoffice.security.mfa.verify'), [
                    'code' => '000000',
                ])
                ->assertForbidden();
        }
    }

    private function authorizedPlatformOperator(): User
    {
        return $this->platformUser([
            'platform_operators.view',
            'security.manage_own_mfa',
        ]);
    }
}
