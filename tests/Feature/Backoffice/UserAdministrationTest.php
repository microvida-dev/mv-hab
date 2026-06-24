<?php

namespace Tests\Feature\Backoffice;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserAdministrationService;
use Database\Seeders\SystemAccessSeeder;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_technician_without_permission_cannot_create_backoffice_user(): void
    {
        $technician = $this->userWithRole('municipal_technician');

        $this
            ->actingAs($technician)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.store'), [
                'name' => 'Utilizador Bloqueado',
                'email' => 'blocked-qa30@example.test',
                'role' => 'support_agent',
                'status' => 'active',
                'justification' => 'Tentativa sem permissão.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'blocked-qa30@example.test']);
    }

    public function test_deactivated_backoffice_user_cannot_access_backoffice(): void
    {
        $administrator = $this->userWithRole('administrator', ['status' => 'inactive']);

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.users.index'))
            ->assertForbidden();
    }

    public function test_last_active_administrator_is_protected_from_deactivation(): void
    {
        $permission = Permission::query()->where('name', 'users.deactivate')->firstOrFail();
        $managerRole = Role::query()->create(['name' => 'user_manager_test', 'label' => 'User manager test', 'scope' => 'test']);
        $managerRole->permissions()->attach($permission);

        $actor = User::factory()->create();
        $actor->roles()->attach($managerRole);
        $target = $this->userWithRole('administrator');

        $this->expectException(DomainException::class);

        app(UserAdministrationService::class)->deactivate($actor, $target, 'Teste de proteção do último administrator.');
    }

    public function test_reset_password_uses_laravel_broker_without_exposing_password(): void
    {
        Notification::fake();

        $administrator = $this->userWithRole('administrator');
        $target = $this->userWithRole('support_agent');

        $this
            ->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.reset-password', $target), [
                'justification' => 'Pedido de reset QA30.',
            ])
            ->assertRedirect();

        Notification::assertSentTo($target, ResetPassword::class);
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'user_password_reset_requested',
            'target_user_id' => $target->id,
        ]);
    }

    public function test_auditor_cannot_mutate_users(): void
    {
        $auditor = $this->userWithRole('auditor');
        $target = $this->userWithRole('support_agent');

        $this
            ->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.deactivate', $target), [
                'justification' => 'Auditor não deve alterar utilizadores.',
            ])
            ->assertForbidden();

        $this->assertSame('active', $target->refresh()->status);
    }

    public function test_self_deactivation_of_administrator_is_blocked(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->expectException(AuthorizationException::class);

        app(UserAdministrationService::class)->deactivate($administrator, $administrator, 'Auto-desativação crítica.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => $role.'-users-qa30-'.fake()->unique()->numerify('####').'@example.test',
        ], $attributes));
        $user->assignRole($role);

        return $user;
    }
}
