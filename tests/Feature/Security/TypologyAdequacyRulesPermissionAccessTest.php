<?php

namespace Tests\Feature\Security;

use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\TypologyAdequacyRule;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TypologyAdequacyRulesPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_typology_adequacy_rule_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.typology-rules.index' => 'permission:allocations.view',
            'backoffice.allocation.typology-rules.create' => 'permission:allocations.create',
            'backoffice.allocation.typology-rules.store' => 'permission:allocations.create',
            'backoffice.allocation.typology-rules.edit' => 'permission:allocations.update',
            'backoffice.allocation.typology-rules.update' => 'permission:allocations.update',
            'backoffice.allocation.typology-rules.activate' => 'permission:allocations.update',
            'backoffice.allocation.typology-rules.deactivate' => 'permission:allocations.update',
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
                "Route [{$routeName}] still contains active role middleware.",
            );
        }
    }

    public function test_view_permission_can_access_index_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $rule = $this->ruleFor($user);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.create'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.edit', $rule),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.activate', $rule),
        )->assertForbidden();
    }

    public function test_create_permission_can_create_but_does_not_grant_view_or_update(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $rule = $this->ruleFor($user);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.create'),
        )->assertOk();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.store'),
            $this->validPayload($user, ['name' => 'Regra autorizada']),
        )->assertRedirect();

        $this->assertDatabaseHas('typology_adequacy_rules', [
            'name' => 'Regra autorizada',
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.edit', $rule),
        )->assertForbidden();
    }

    public function test_update_permission_can_update_activate_and_deactivate_but_not_view_or_create(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $rule = $this->ruleFor($user, [
            'is_active' => false,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.edit', $rule),
        )->assertOk();

        $this->putAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.update', $rule),
            $this->validPayload($user, ['name' => 'Regra atualizada']),
        )->assertRedirect();

        $this->assertDatabaseHas('typology_adequacy_rules', [
            'id' => $rule->id,
            'name' => 'Regra atualizada',
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.activate', $rule),
        )->assertRedirect();

        $this->assertTrue($rule->fresh()->is_active);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.deactivate', $rule),
        )->assertRedirect();

        $this->assertFalse($rule->fresh()->is_active);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.create'),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked(): void
    {
        $user = $this->userWithCustomRole([]);
        $rule = $this->ruleFor($user);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.activate', $rule),
        )->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_all_typology_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $rule = $this->ruleFor($candidate, [
            'is_active' => false,
        ]);

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.typology-rules.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.typology-rules.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.typology-rules.activate', $rule),
        )->assertForbidden();

        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_auditor_can_read_but_cannot_create_update_activate_or_deactivate(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $rule = $this->ruleFor($auditor, [
            'is_active' => false,
        ]);

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.typology-rules.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.typology-rules.create'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.typology-rules.edit', $rule),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.typology-rules.activate', $rule),
        )->assertForbidden();

        $this->assertFalse($rule->fresh()->is_active);
    }

    public function test_backoffice_policy_abilities_match_permission_and_role_boundaries(): void
    {
        $viewer = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $creator = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $updater = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $rule = $this->ruleFor($viewer);

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                TypologyAdequacyRule::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                TypologyAdequacyRule::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $rule,
            ),
        );

        $this->assertFalse(
            Gate::forUser($viewer)->allows(
                'createBackoffice',
                TypologyAdequacyRule::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($creator)->allows(
                'updateBackoffice',
                $rule,
            ),
        );

        $this->assertFalse(
            Gate::forUser($updater)->allows(
                'viewAnyBackoffice',
                TypologyAdequacyRule::class,
            ),
        );
    }

    public function test_update_request_uses_update_backoffice_instead_of_create_backoffice(): void
    {
        $creator = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $updater = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $rule = $this->ruleFor($updater);

        $this->assertFalse(
            Gate::forUser($creator)->allows(
                'updateBackoffice',
                $rule,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $rule,
            ),
        );

        $this->putAsBackofficeUser(
            $creator,
            route('backoffice.allocation.typology-rules.update', $rule),
            $this->validPayload($creator, ['name' => 'Tentativa recusada']),
        )->assertForbidden();

        $this->assertDatabaseMissing('typology_adequacy_rules', [
            'id' => $rule->id,
            'name' => 'Tentativa recusada',
        ]);
    }

    public function test_rule_from_another_municipality_is_not_listed_or_mutable(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
            'allocations.update',
        ]);
        $foreignUser = User::factory()->create([
            'municipality_id' => Municipality::factory()->create()->id,
        ]);
        $foreignRule = $this->ruleFor($foreignUser, [
            'name' => 'Regra de outro município',
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.index'),
        )
            ->assertOk()
            ->assertDontSee('Regra de outro município');

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.typology-rules.edit', $foreignRule),
        )->assertForbidden();
    }

    private function getAsBackofficeUser(
        User $user,
        string $uri,
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($uri);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postAsBackofficeUser(
        User $user,
        string $uri,
        array $data = [],
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post($uri, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function putAsBackofficeUser(
        User $user,
        string $uri,
        array $data = [],
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->put($uri, $data);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(User $user, array $overrides = []): array
    {
        $contest = $this->contestFor($user);

        return array_merge([
            'program_id' => null,
            'contest_id' => $contest->id,
            'name' => 'Regra de adequação',
            'description' => 'Adequação tipológica de teste.',
            'is_active' => true,
            'min_household_members' => 1,
            'max_household_members' => 4,
            'min_adults' => 1,
            'max_adults' => 3,
            'min_children' => 0,
            'max_children' => 2,
            'min_bedrooms' => 1,
            'max_bedrooms' => 3,
            'typology' => 'T2',
            'requires_accessibility' => false,
            'special_condition_key' => null,
            'priority_order' => 10,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function ruleFor(
        User $user,
        array $overrides = [],
    ): TypologyAdequacyRule {
        $contest = $this->contestFor($user);

        return TypologyAdequacyRule::factory()->create(array_merge([
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
        ], $overrides));
    }

    private function contestFor(User $user): Contest
    {
        $program = Program::factory()->create([
            'municipality_id' => $user->municipality_id,
        ]);

        return Contest::factory()->for($program)->create();
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
            'name' => 'typology_rules_'.str()->random(8),
            'label' => 'Typology adequacy rule permission test role',
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
