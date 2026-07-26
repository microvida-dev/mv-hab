<?php

namespace Tests\Feature\Security;

use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HousingUnitPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_housing_unit_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'housing-units.index' => 'permission:housing_units.view',
            'housing-units.create' => 'permission:housing_units.create',
            'housing-units.store' => 'permission:housing_units.create',
            'housing-units.show' => 'permission:housing_units.view',
            'housing-units.edit' => 'permission:housing_units.update',
            'housing-units.update' => 'permission:housing_units.update',
            'housing-units.destroy' => 'permission:housing_units.delete',

            'backoffice.allocation.contest-housing-units.index' => 'permission:allocations.view',
            'backoffice.allocation.contest-housing-units.create' => 'permission:allocations.create',
            'backoffice.allocation.contest-housing-units.store' => 'permission:allocations.create',
            'backoffice.allocation.contest-housing-units.show' => 'permission:allocations.view',
            'backoffice.allocation.contest-housing-units.edit' => 'permission:allocations.update',
            'backoffice.allocation.contest-housing-units.update' => 'permission:allocations.update',
            'backoffice.allocation.contest-housing-units.destroy' => 'permission:allocations.update',
            'backoffice.allocation.contest-housing-units.mark-available' => 'permission:allocations.update',
            'backoffice.allocation.contest-housing-units.mark-unavailable' => 'permission:allocations.update',
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
                "Route [{$routeName}] does not exclude the inherited fixed role middleware.",
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
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
                "Route [{$routeName}] still contains active fixed role middleware.",
            );
        }
    }

    public function test_user_with_housing_unit_view_permission_can_access_housing_unit_pages(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.view',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.show', $housingUnit))
            ->assertOk();
    }

    public function test_user_with_allocation_view_permission_can_access_contest_housing_unit_pages(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $contestHousingUnit = ContestHousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.allocation.contest-housing-units.show',
                $contestHousingUnit,
            ))
            ->assertOk();
    }

    public function test_user_without_view_permissions_cannot_access_housing_unit_indexes(): void
    {
        $user = $this->userWithCustomRole([]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertForbidden();
    }

    public function test_housing_unit_and_allocation_permissions_are_isolated(): void
    {
        $housingUnitViewer = $this->userWithCustomRole([
            'housing_units.view',
        ]);

        $this->actingAs($housingUnitViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertOk();

        $this->actingAs($housingUnitViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertForbidden();

        $allocationViewer = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $this->actingAs($allocationViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertOk();

        $this->actingAs($allocationViewer)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_housing_and_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'housing_units.view',
            'housing_units.create',
            'housing_units.update',
            'housing_units.delete',
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertForbidden();
    }

    public function test_auditor_can_view_but_cannot_change_housing_units_or_assignments(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'housing_units.view',
            'housing_units.create',
            'housing_units.update',
            'housing_units.delete',
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $housingUnit = HousingUnit::factory()->create();
        $contestHousingUnit = ContestHousingUnit::factory()->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.show', $housingUnit))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.allocation.contest-housing-units.show',
                $contestHousingUnit,
            ))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.edit', $housingUnit))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('housing-units.destroy', $housingUnit))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.allocation.contest-housing-units.edit',
                $contestHousingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route(
                'backoffice.allocation.contest-housing-units.destroy',
                $contestHousingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.allocation.contest-housing-units.mark-available',
                $contestHousingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.allocation.contest-housing-units.mark-unavailable',
                $contestHousingUnit,
            ))
            ->assertForbidden();
    }

    public function test_allocation_create_permission_does_not_grant_update_permission(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $contestHousingUnit = ContestHousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.allocation.contest-housing-units.create'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.allocation.contest-housing-units.edit',
                $contestHousingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.allocation.contest-housing-units.mark-unavailable',
                $contestHousingUnit,
            ))
            ->assertForbidden();
    }

    public function test_housing_unit_update_permission_does_not_grant_delete_permission(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.update',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('housing-units.edit', $housingUnit))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('housing-units.destroy', $housingUnit))
            ->assertForbidden();

        $this->assertDatabaseHas('housing_units', [
            'id' => $housingUnit->id,
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
            'name' => 'housing_unit_'.str()->random(8),
            'label' => 'Housing unit permission test role',
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
