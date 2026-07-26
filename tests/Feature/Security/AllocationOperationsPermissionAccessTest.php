<?php

namespace Tests\Feature\Security;

use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\Permission;
use App\Models\ReserveList;
use App\Models\Role;
use App\Models\User;
use App\Services\Allocation\AllocationOfferService;
use App\Services\Allocation\AllocationResponseService;
use App\Services\Allocation\ReplacementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class AllocationOperationsPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_allocation_operation_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.allocations.index' => 'permission:allocations.view',

            'backoffice.allocation.allocations.manual-create' => 'permission:allocations.create',

            'backoffice.allocation.allocations.manual-store' => 'permission:allocations.create',

            'backoffice.allocation.allocations.show' => 'permission:allocations.view',

            'backoffice.allocation.offers.index' => 'permission:allocations.view',

            'backoffice.allocation.offers.show' => 'permission:allocations.view',

            'backoffice.allocation.offers.issue' => 'permission:allocations.update',

            'backoffice.allocation.offers.mark-expired' => 'permission:allocations.update',

            'backoffice.allocation.reserve-lists.index' => 'permission:allocations.view',

            'backoffice.allocation.reserve-lists.show' => 'permission:allocations.view',

            'backoffice.allocation.reserve-lists.call-next' => 'permission:allocations.update',
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

    public function test_view_permission_can_read_allocations_offers_and_reserve_lists(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $allocation = Allocation::factory()->create();
        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.show', $allocation),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.show', $offer),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reserve-lists.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reserve-lists.show', $reserveList),
        )->assertOk();
    }

    public function test_view_permission_does_not_grant_creation_or_transition_access(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.manual-create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.issue', $offer),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.mark-expired', $offer),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reserve-lists.call-next', $reserveList),
            [],
        )->assertForbidden();
    }

    public function test_create_permission_grants_manual_creation_form_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.manual-create'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.issue', $offer),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reserve-lists.call-next', $reserveList),
            [],
        )->assertForbidden();
    }

    public function test_update_permission_authorizes_offer_issue(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $offer = AllocationOffer::factory()->create();

        $this->mock(
            AllocationOfferService::class,
            function (MockInterface $mock) use ($offer): void {
                $mock->shouldReceive('issue')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationOffer $receivedOffer,
                            User $actor,
                        ): bool => $receivedOffer->is($offer)
                            && $actor->id !== null,
                    )
                    ->andReturn($offer);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.issue', $offer),
        )->assertRedirect();
    }

    public function test_update_permission_authorizes_offer_expiration(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $offer = AllocationOffer::factory()->create();

        $this->mock(
            AllocationResponseService::class,
            function (MockInterface $mock) use ($offer): void {
                $mock->shouldReceive('expire')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationOffer $receivedOffer,
                            User $actor,
                        ): bool => $receivedOffer->is($offer)
                            && $actor->id !== null,
                    )
                    ->andReturn($offer);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.mark-expired', $offer),
        )->assertRedirect();
    }

    public function test_update_permission_authorizes_calling_next_reserve_candidate(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $reserveList = ReserveList::factory()->create();
        $allocation = Allocation::factory()->create();

        $this->mock(
            ReplacementService::class,
            function (MockInterface $mock) use ($allocation): void {
                $mock->shouldReceive('callNextFor')
                    ->once()
                    ->withArgs(
                        fn (
                            Allocation $receivedAllocation,
                            User $actor,
                        ): bool => $receivedAllocation->is($allocation)
                            && $actor->id !== null,
                    )
                    ->andReturn(null);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route(
                'backoffice.allocation.reserve-lists.call-next',
                $reserveList,
            ),
            [
                'replacement_for_allocation_id' => $allocation->id,
            ],
        )->assertRedirect();
    }

    public function test_update_permission_does_not_grant_view_or_creation_access(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.update',
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.manual-create'),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked(): void
    {
        $user = $this->userWithCustomRole([]);

        $allocation = Allocation::factory()->create();
        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.allocations.show', $allocation),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.offers.show', $offer),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reserve-lists.show', $reserveList),
        )->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_all_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $allocation = Allocation::factory()->create([
            'user_id' => $candidate->id,
        ]);

        $offer = AllocationOffer::factory()->create([
            'user_id' => $candidate->id,
        ]);

        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.allocations.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.allocations.show', $allocation),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.offers.show', $offer),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.reserve-lists.show', $reserveList),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.offers.issue', $offer),
        )->assertForbidden();
    }

    public function test_auditor_can_read_but_cannot_create_or_update(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.update',
        ]);

        $allocation = Allocation::factory()->create();
        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.allocations.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.allocations.show', $allocation),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.offers.show', $offer),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reserve-lists.show', $reserveList),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.allocations.manual-create'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.offers.issue', $offer),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.offers.mark-expired', $offer),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reserve-lists.call-next', $reserveList),
            [],
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

        $allocation = Allocation::factory()->create();
        $offer = AllocationOffer::factory()->create();
        $reserveList = ReserveList::factory()->create();

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                Allocation::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewBackoffice',
                $allocation,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                Allocation::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $offer,
            ),
        );

        $this->assertTrue(
            Gate::forUser($updater)->allows(
                'updateBackoffice',
                $reserveList,
            ),
        );

        $this->assertFalse(
            Gate::forUser($viewer)->allows(
                'createBackoffice',
                Allocation::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($creator)->allows(
                'updateBackoffice',
                $offer,
            ),
        );

        $this->assertFalse(
            Gate::forUser($updater)->allows(
                'viewAnyBackoffice',
                Allocation::class,
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
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'allocation_operations_'.str()->random(8),
            'label' => 'Allocation operations permission test role',
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
