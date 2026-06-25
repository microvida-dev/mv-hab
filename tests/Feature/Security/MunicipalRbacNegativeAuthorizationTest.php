<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalRbacNegativeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_non_administrators_cannot_manage_users_roles_or_teams(): void
    {
        foreach (['municipal_technician', 'jury', 'financial_manager', 'maintenance_manager', 'legal_manager', 'housing_manager', 'inspection_manager', 'support_agent', 'auditor', 'candidate'] as $role) {
            $user = $this->userWithRole($role);

            $this->assertFalse($user->hasPermission('users.create'), "{$role} should not create users");
            $this->assertFalse($user->hasPermission('roles.assign'), "{$role} should not assign roles");
            $this->assertFalse($user->hasPermission('teams.manage_members'), "{$role} should not manage team members");
        }
    }

    public function test_role_boundaries_prevent_cross_domain_escalation(): void
    {
        $financial = $this->userWithRole('financial_manager');
        $maintenance = $this->userWithRole('maintenance_manager');
        $support = $this->userWithRole('support_agent');
        $legal = $this->userWithRole('legal_manager');

        $this->assertFalse($financial->hasPermission('scoring.update'));
        $this->assertFalse($maintenance->hasPermission('documents.view'));
        $this->assertFalse($support->hasPermission('documents.view'));
        $this->assertFalse($support->hasPermission('payments.view'));
        $this->assertFalse($legal->hasPermission('payments.update'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
