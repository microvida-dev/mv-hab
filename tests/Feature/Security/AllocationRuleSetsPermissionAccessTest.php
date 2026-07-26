<?php

namespace Tests\Feature\Security;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AllocationRuleSetsPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_allocation_rule_set_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.rule-sets.index' => 'permission:allocations.view',
            'backoffice.allocation.rule-sets.create' => 'permission:allocations.create',
            'backoffice.allocation.rule-sets.store' => 'permission:allocations.create',
            'backoffice.allocation.rule-sets.show' => 'permission:allocations.view',
            'backoffice.allocation.rule-sets.edit' => 'permission:allocations.update',
            'backoffice.allocation.rule-sets.update' => 'permission:allocations.update',
            'backoffice.allocation.rule-sets.activate' => 'permission:allocations.approve',
            'backoffice.allocation.rule-sets.archive' => 'permission:allocations.update',
            'backoffice.allocation.rule-sets.duplicate' => 'permission:allocations.create',
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

    public function test_view_permission_can_read_rule_sets_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.show', $ruleSet),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.duplicate', $ruleSet),
        )->assertForbidden();
    }

    public function test_create_permission_can_create_and_duplicate_without_update_or_approve(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'name' => 'Regra original',
            'status' => AllocationRuleSetStatus::Active,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.create'),
        )->assertOk();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.store'),
            $this->validPayload(['name' => 'Regra criada']),
        )->assertRedirect();

        $this->assertDatabaseHas('allocation_rule_sets', [
            'name' => 'Regra criada',
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.duplicate', $ruleSet),
        )->assertRedirect();

        $this->assertDatabaseHas('allocation_rule_sets', [
            'name' => 'Regra original (cópia)',
            'status' => AllocationRuleSetStatus::Draft->value,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.edit', $ruleSet),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertForbidden();
    }

    public function test_update_permission_can_update_and_archive_but_not_view_create_or_activate(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'status' => AllocationRuleSetStatus::Draft,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.edit', $ruleSet),
        )->assertOk();

        $this->putAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.update', $ruleSet),
            $this->validPayload(['name' => 'Regra atualizada']),
        )->assertRedirect();

        $this->assertDatabaseHas('allocation_rule_sets', [
            'id' => $ruleSet->id,
            'name' => 'Regra atualizada',
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.archive', $ruleSet),
        )->assertRedirect();

        $this->assertSame(
            AllocationRuleSetStatus::Archived,
            $ruleSet->fresh()->status,
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertForbidden();
    }

    public function test_approve_permission_can_activate_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'status' => AllocationRuleSetStatus::Draft,
        ]);

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertRedirect();

        $this->assertSame(
            AllocationRuleSetStatus::Active,
            $ruleSet->fresh()->status,
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.edit', $ruleSet),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked_and_does_not_change_state(): void
    {
        $user = $this->userWithCustomRole([]);
        $ruleSet = AllocationRuleSet::factory()->create([
            'status' => AllocationRuleSetStatus::Draft,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.rule-sets.archive', $ruleSet),
        )->assertForbidden();

        $this->assertSame(
            AllocationRuleSetStatus::Draft,
            $ruleSet->fresh()->status,
        );
    }

    public function test_candidate_is_blocked_even_with_all_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'status' => AllocationRuleSetStatus::Draft,
        ]);

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.rule-sets.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.rule-sets.show', $ruleSet),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertForbidden();

        $this->assertSame(
            AllocationRuleSetStatus::Draft,
            $ruleSet->fresh()->status,
        );
    }

    public function test_auditor_can_read_but_cannot_create_update_approve_or_duplicate(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
            'allocations.approve',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create([
            'status' => AllocationRuleSetStatus::Draft,
        ]);

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.show', $ruleSet),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.create'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.edit', $ruleSet),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.activate', $ruleSet),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.rule-sets.duplicate', $ruleSet),
        )->assertForbidden();
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

        $approver = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $ruleSet = AllocationRuleSet::factory()->create();

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                AllocationRuleSet::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewBackoffice',
                $ruleSet,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                AllocationRuleSet::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $ruleSet,
            ),
        );

        $this->assertTrue(
            Gate::forUser($approver)->allows(
                'approveBackoffice',
                $ruleSet,
            ),
        );

        $this->assertFalse(
            Gate::forUser($creator)->allows(
                'updateBackoffice',
                $ruleSet,
            ),
        );

        $this->assertFalse(
            Gate::forUser($updater)->allows(
                'approveBackoffice',
                $ruleSet,
            ),
        );

        $this->assertFalse(
            Gate::forUser($approver)->allows(
                'viewAnyBackoffice',
                AllocationRuleSet::class,
            ),
        );
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
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'program_id' => null,
            'contest_id' => Contest::factory()->create()->id,
            'name' => 'Conjunto de regras de atribuição',
            'description' => 'Configuração de teste.',
            'status' => AllocationRuleSetStatus::Draft->value,
            'allocation_method' => AllocationMethod::Ranking->value,
            'allow_preferences' => true,
            'allow_lottery' => false,
            'allow_manual_override' => false,
            'requires_acceptance' => true,
            'acceptance_deadline_days' => 10,
            'auto_call_next_on_refusal' => true,
            'auto_call_next_on_expiry' => true,
            'max_refusals_allowed' => 1,
        ], $overrides);
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
            'name' => 'allocation_rule_sets_'.str()->random(8),
            'label' => 'Allocation rule sets permission test role',
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
