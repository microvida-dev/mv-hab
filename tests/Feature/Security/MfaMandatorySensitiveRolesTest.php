<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Security\MfaDeviceService;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MfaMandatorySensitiveRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_sensitive_roles_require_mfa_and_mfa_changes_are_audited(): void
    {
        $mfa = app(MfaEnforcementService::class);
        $devices = app(MfaDeviceService::class);

        foreach (['administrator', 'municipal_technician', 'jury', 'legal_manager', 'financial_manager', 'housing_manager', 'inspection_manager', 'auditor'] as $role) {
            $this->assertTrue($mfa->requiresMfa($this->userWithRole($role)), $role);
        }

        $administrator = $this->userWithRole('administrator');
        $device = $devices->createTotpDevice($administrator);
        $this->assertTrue($devices->confirm($device, $devices->totp($device->secret_encrypted), $administrator));
        $devices->disable($device, $administrator);

        $this->assertDatabaseHas('audit_events', ['event_code' => 'mfa_enabled']);
        $this->assertDatabaseHas('audit_events', ['event_code' => 'mfa_disabled']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
