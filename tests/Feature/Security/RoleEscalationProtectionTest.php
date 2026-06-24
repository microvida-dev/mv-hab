<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleAssignmentService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleEscalationProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_administrator_cannot_self_assign_role_through_role_management_flow(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.roles.assign', $administrator), [
                'role' => 'financial_manager',
                'justification' => 'Tentativa de alteração da própria role.',
            ])
            ->assertForbidden();
    }

    public function test_user_without_role_permission_cannot_assign_roles(): void
    {
        $technician = $this->userWithRole('municipal_technician');
        $target = $this->userWithRole('support_agent');

        $this
            ->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.roles.assign', $target), [
                'role' => 'legal_manager',
                'justification' => 'Tentativa sem permissão.',
            ])
            ->assertForbidden();

        $this->assertFalse($target->refresh()->hasRole('legal_manager'));
    }

    public function test_non_administrator_cannot_assign_administrator_even_with_assign_permission(): void
    {
        $assign = Permission::query()->where('name', 'roles.assign')->firstOrFail();
        $limitedRole = Role::query()->create(['name' => 'limited_role_assigner', 'label' => 'Limited role assigner', 'scope' => 'test']);
        $limitedRole->permissions()->attach($assign);

        $actor = User::factory()->create();
        $actor->roles()->attach($limitedRole);
        $target = $this->userWithRole('support_agent');
        $administratorRole = Role::query()->where('name', 'administrator')->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(RoleAssignmentService::class)->assign($actor, $target, $administratorRole, 'Tentativa de escalada controlada.');
    }

    public function test_support_agent_cannot_access_role_management(): void
    {
        $support = $this->userWithRole('support_agent');

        $this
            ->actingAs($support)
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['email' => $role.'-security-qa30-'.fake()->unique()->numerify('####').'@example.test']);
        $user->assignRole($role);

        return $user;
    }
}
