<?php

namespace Tests\Feature\Backoffice;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_role_management_routes_use_permissions_and_all_backoffice_guards(): void
    {
        $expected = [
            'backoffice.roles.index' => 'permission:roles.view',
            'backoffice.roles.create' => 'permission:roles.create',
            'backoffice.roles.store' => 'permission:roles.create',
            'backoffice.roles.show' => 'permission:roles.view',
            'backoffice.roles.edit' => 'permission:roles.update',
            'backoffice.roles.update' => 'permission:roles.update',
            'backoffice.roles.permissions.update' => 'permission:roles.update',
            'backoffice.roles.duplicate' => 'permission:roles.create',
            'backoffice.roles.activate' => 'permission:roles.update',
            'backoffice.roles.deactivate' => 'permission:roles.update',
            'backoffice.roles.users' => 'permission:roles.view',
            'backoffice.roles.audit' => 'permission:roles.audit',
            'backoffice.roles.destroy' => 'permission:roles.delete',
            'backoffice.users.roles.assign' => 'permission:roles.assign',
            'backoffice.users.roles.remove' => 'permission:roles.remove',
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware, $routeName);
            $this->assertContains('active.backoffice', $middleware, $routeName);
            $this->assertContains('mfa.backoffice', $middleware, $routeName);
            $this->assertContains('log.backoffice', $middleware, $routeName);
            $this->assertContains($permission, $middleware, $routeName);
            $this->assertFalse(
                collect($middleware)->contains(fn (string $item): bool => str_starts_with($item, 'role:')),
                $routeName,
            );
        }
    }

    public function test_guest_candidate_and_user_without_permission_are_blocked(): void
    {
        $this->get(route('backoffice.roles.index'))->assertRedirect(route('login'));

        $candidate = $this->userWithRole('candidate');
        $this->grantPermissionThroughCustomRole($candidate, 'candidate_role_reader', ['roles.view']);

        $this->actingAs($candidate)
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();

        $support = $this->userWithRole('support_agent');
        $this->actingAs($support)
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();
    }

    public function test_authorized_administrator_can_create_update_and_sync_custom_role(): void
    {
        $administrator = $this->userWithRole('administrator');
        $applicationView = Permission::query()->where('name', 'applications.view')->firstOrFail();
        $documentView = Permission::query()->where('name', 'documents.view')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'label' => 'Operador de recolha municipal',
                'description' => 'Recolha e consulta inicial.',
                'permissions' => [$applicationView->id],
                'justification' => 'Criação do perfil de menor privilégio.',
            ])
            ->assertRedirect();

        $role = Role::query()->where('label', 'Operador de recolha municipal')->firstOrFail();

        $this->assertSame('operador_de_recolha_municipal', $role->name);
        $this->assertTrue($role->isMunicipalCustom());
        $this->assertTrue($role->isActive());
        $this->assertTrue($role->permissions()->where('name', 'applications.view')->exists());
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'role_created',
            'role_id' => $role->id,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.update', $role), [
                'label' => 'Operador de recolha',
                'description' => 'Recolha municipal controlada.',
                'name' => 'administrator',
                'scope' => 'system',
                'is_system' => true,
                'justification' => 'Clarificação da designação visual.',
            ])
            ->assertRedirect(route('backoffice.roles.show', $role));

        $role->refresh();
        $this->assertSame('operador_de_recolha_municipal', $role->name);
        $this->assertSame('municipal', $role->scope);
        $this->assertFalse($role->isSystem());
        $this->assertSame('Operador de recolha', $role->label);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.permissions.update', $role), [
                'permissions' => [$applicationView->id, $documentView->id],
                'justification' => 'Adicionar consulta documental necessária.',
            ])
            ->assertRedirect(route('backoffice.roles.edit', $role));

        $this->assertEqualsCanonicalizing(
            ['applications.view', 'documents.view'],
            $role->permissions()->pluck('name')->all(),
        );
    }

    public function test_role_can_be_duplicated_toggled_and_deleted_only_when_unused(): void
    {
        $administrator = $this->userWithRole('administrator');
        $source = Role::query()->where('name', 'municipal_technician')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.duplicate', $source), [
                'label' => 'Técnico municipal adaptado',
                'description' => 'Cópia municipal para adaptação.',
                'justification' => 'Duplicação para configuração local.',
            ])
            ->assertRedirect();

        $copy = Role::query()->where('label', 'Técnico municipal adaptado')->firstOrFail();
        $target = User::factory()->create(['status' => 'active']);
        $target->roles()->attach($copy);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.deactivate', $copy), [
                'justification' => 'Suspensão temporária do perfil.',
            ])
            ->assertRedirect();

        $this->assertFalse($copy->refresh()->isActive());
        $this->assertFalse($target->hasPermission('applications.view'));

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.activate', $copy), [
                'justification' => 'Reativação após revisão.',
            ])
            ->assertRedirect();

        $this->assertTrue($copy->refresh()->isActive());

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.roles.destroy', $copy), [
                'justification' => 'Tentativa controlada com utilizador associado.',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $copy->id]);

        $target->roles()->detach($copy);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.roles.destroy', $copy), [
                'justification' => 'Eliminação após remoção das associações.',
            ])
            ->assertRedirect(route('backoffice.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $copy->id]);
        $this->assertDatabaseHas('access_change_events', ['event_code' => 'role_deleted']);
    }

    public function test_system_role_is_read_only_and_auditor_cannot_mutate_even_with_update_permission(): void
    {
        $administrator = $this->userWithRole('administrator');
        $systemRole = Role::query()->where('name', 'municipal_technician')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.show', $systemRole))
            ->assertOk()
            ->assertSee('modo de leitura');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.update', $systemRole), [
                'label' => 'Tentativa proibida',
                'justification' => 'Teste de proteção.',
            ])
            ->assertForbidden();

        $auditor = $this->userWithRole('auditor');
        $this->grantPermissionThroughCustomRole($auditor, 'auditor_role_update_probe', ['roles.update']);
        $custom = $this->customRole('perfil_auditado', ['applications.view']);

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->patch(route('backoffice.roles.update', $custom), [
                'label' => 'Alteração indevida',
                'justification' => 'Teste read-only do auditor.',
            ])
            ->assertForbidden();

        $this->assertSame('Perfil Auditado', $custom->refresh()->label);
    }

    public function test_inactive_role_cannot_be_assigned_and_users_are_paginated(): void
    {
        $administrator = $this->userWithRole('administrator');
        $inactive = $this->customRole('perfil_inativo', ['applications.view'], false);
        $target = User::factory()->create(['status' => 'active']);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.users.roles.assign', $target), [
                'role' => $inactive->name,
                'justification' => 'Tentativa de atribuição de perfil inativo.',
            ])
            ->assertSessionHasErrors('role');

        $this->assertFalse($target->roles()->whereKey($inactive->id)->exists());

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.users', $inactive))
            ->assertOk()
            ->assertSee('Não existem utilizadores associados.');
    }

    /** @param list<string> $permissions */
    private function customRole(string $name, array $permissions, bool $active = true): Role
    {
        $role = Role::query()->create([
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $active,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all(),
        );

        return $role;
    }

    /** @param list<string> $permissions */
    private function grantPermissionThroughCustomRole(User $user, string $roleName, array $permissions): void
    {
        $user->roles()->attach($this->customRole($roleName, $permissions));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }
}
