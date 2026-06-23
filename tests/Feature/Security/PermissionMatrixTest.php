<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_system_roles_resolve_expected_permission_boundaries(): void
    {
        $administrator = $this->userWithRole('administrator');
        $technician = $this->userWithRole('municipal_technician');
        $jury = $this->userWithRole('jury');
        $financial = $this->userWithRole('financial_manager');
        $maintenance = $this->userWithRole('maintenance_manager');
        $candidate = $this->userWithRole('candidate');
        $auditor = $this->userWithRole('auditor');

        $this->assertTrue($administrator->hasPermission('settings.update'));
        $this->assertTrue($technician->hasPermission('applications.update'));
        $this->assertTrue($jury->hasPermission('scoring.approve'));
        $this->assertTrue($financial->hasPermission('finance.approve'));
        $this->assertTrue($maintenance->hasPermission('maintenance_requests.update'));
        $this->assertTrue($candidate->hasPermission('applications.create'));
        $this->assertTrue($auditor->hasPermission('audit_logs.view'));

        $this->assertFalse($candidate->hasPermission('settings.update'));
        $this->assertFalse($financial->hasPermission('scoring.update'));
        $this->assertFalse($maintenance->hasPermission('income_records.view'));
        $this->assertFalse($jury->hasPermission('settings.update'));
        $this->assertFalse($technician->hasPermission('users.delete'));
        $this->assertFalse($auditor->hasPermission('applications.update'));
    }

    public function test_route_matrix_blocks_guest_candidate_and_requires_mfa_for_sensitive_backoffice(): void
    {
        $this->get(route('backoffice.reports.index'))->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $administrator = $this->userWithRole('administrator');
        $financial = $this->userWithRole('financial_manager');
        $maintenance = $this->userWithRole('maintenance_manager');

        $this->actingAs($candidate)
            ->get(route('backoffice.reports.index'))
            ->assertForbidden();

        $this->actingAs($financial)
            ->get(route('backoffice.finance.installments.index'))
            ->assertOk();

        $this->actingAs($maintenance)
            ->get(route('backoffice.maintenance.requests.index'))
            ->assertOk();

        $this->actingAs($administrator)
            ->get(route('backoffice.security.dashboard'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.security.dashboard'))
            ->assertOk();
    }

    private function userWithRole(string $role): User
    {
        $this->assertTrue(Role::query()->where('name', $role)->exists());
        $user = User::factory()->create(['email' => 's19-'.$role.'-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
