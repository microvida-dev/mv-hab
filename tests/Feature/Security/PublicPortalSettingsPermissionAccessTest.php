<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\PublicPortalLink;
use App\Models\PublicPortalSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicPortalSettingsPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_public_portal_settings_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.public-portal.settings.edit' => 'permission:settings.view',

            'backoffice.public-portal.settings.update' => 'permission:settings.update',

            'backoffice.public-portal.links.index' => 'permission:settings.view',

            'backoffice.public-portal.links.create' => 'permission:settings.create',

            'backoffice.public-portal.links.store' => 'permission:settings.create',

            'backoffice.public-portal.links.edit' => 'permission:settings.update',

            'backoffice.public-portal.links.update' => 'permission:settings.update',

            'backoffice.public-portal.links.destroy' => 'permission:settings.delete',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
                "Route [{$routeName}] does not exclude inherited fixed-role middleware.",
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains($permissionMiddleware, $middleware);

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                "Route [{$routeName}] still contains active fixed-role middleware.",
            );
        }
    }

    public function test_user_with_settings_view_permission_can_read_settings_and_links(): void
    {
        $user = $this->userWithCustomRole([
            'settings.view',
        ]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.settings.edit'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.index'))
            ->assertOk();
    }

    public function test_settings_view_permission_does_not_grant_mutation_access(): void
    {
        $user = $this->userWithCustomRole([
            'settings.view',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.public-portal.links.destroy', $link))
            ->assertForbidden();

        $this->assertDatabaseHas('public_portal_links', [
            'id' => $link->id,
        ]);
    }

    public function test_settings_create_permission_grants_link_creation_only(): void
    {
        $user = $this->userWithCustomRole([
            'settings.create',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.public-portal.links.destroy', $link))
            ->assertForbidden();
    }

    public function test_settings_update_permission_grants_settings_and_link_updates_only(): void
    {
        $user = $this->userWithCustomRole([
            'settings.update',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.public-portal.links.destroy', $link))
            ->assertForbidden();

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updateAnyBackoffice',
                PublicPortalSetting::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $link,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'createBackoffice',
                PublicPortalLink::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $link,
            ),
        );
    }

    public function test_settings_delete_permission_grants_link_deletion_only(): void
    {
        $user = $this->userWithCustomRole([
            'settings.delete',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertForbidden();

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $link,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'createBackoffice',
                PublicPortalLink::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $link,
            ),
        );
    }

    public function test_user_without_settings_permissions_is_blocked(): void
    {
        $user = $this->userWithCustomRole([]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.settings.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_all_settings_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.settings.edit'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.public-portal.links.destroy', $link))
            ->assertForbidden();

        $this->assertDatabaseHas('public_portal_links', [
            'id' => $link->id,
        ]);
    }

    public function test_auditor_can_read_but_cannot_modify_settings_or_links(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.settings.edit'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-portal.links.edit', $link))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('backoffice.public-portal.links.destroy', $link))
            ->assertForbidden();

        $this->assertDatabaseHas('public_portal_links', [
            'id' => $link->id,
        ]);
    }

    public function test_update_link_request_uses_update_permission_instead_of_create_permission(): void
    {
        $user = $this->userWithCustomRole([
            'settings.update',
        ]);

        $link = PublicPortalLink::factory()->create();

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'createBackoffice',
                PublicPortalLink::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $link,
            ),
        );

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('backoffice.public-portal.links.update', $link), [
                'label' => 'Ligação pública atualizada',
                'url' => 'https://example.test/ligacao-atualizada',
                'category' => 'Informação',
                'description' => 'Descrição atualizada.',
                'opens_new_tab' => '1',
                'is_active' => '1',
                'sort_order' => 10,
            ])
            ->assertRedirect(
                route('backoffice.public-portal.links.index'),
            );

        $this->assertDatabaseHas('public_portal_links', [
            'id' => $link->id,
            'label' => 'Ligação pública atualizada',
            'url' => 'https://example.test/ligacao-atualizada',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'public_portal_settings_'.str()->random(8),
            'label' => 'Public portal settings permission test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithSystemRoleAndPermissions(
        string $roleName,
        array $permissions,
    ): User {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
