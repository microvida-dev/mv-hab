<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformMunicipalAccessContextTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    private const ROUTE_NAMES = [
        'backoffice.users.index', 'backoffice.users.create', 'backoffice.users.store', 'backoffice.users.show',
        'backoffice.users.edit', 'backoffice.users.update', 'backoffice.users.deactivate', 'backoffice.users.reactivate',
        'backoffice.users.force-mfa', 'backoffice.users.reset-password', 'backoffice.users.roles.assign', 'backoffice.users.roles.remove',
        'backoffice.roles.index', 'backoffice.roles.create', 'backoffice.roles.store', 'backoffice.roles.show',
        'backoffice.roles.edit', 'backoffice.roles.update', 'backoffice.roles.permissions.update', 'backoffice.roles.duplicate',
        'backoffice.roles.activate', 'backoffice.roles.deactivate', 'backoffice.roles.users', 'backoffice.roles.audit', 'backoffice.roles.destroy',
        'backoffice.teams.index', 'backoffice.teams.create', 'backoffice.teams.store', 'backoffice.teams.show',
        'backoffice.teams.edit', 'backoffice.teams.update', 'backoffice.teams.members.store', 'backoffice.teams.members.remove',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_access_routes_have_all_required_guards(): void
    {
        $this->assertCount(33, self::ROUTE_NAMES);
        foreach (self::ROUTE_NAMES as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());
            foreach (['auth', 'active.backoffice', 'mfa.backoffice', 'log.backoffice', 'municipality.context'] as $guard) {
                $this->assertContains($guard, $middleware, $routeName);
            }
            $this->assertTrue(collect($middleware)->contains(fn (string $entry): bool => str_starts_with($entry, 'permission:')), $routeName);
        }
    }

    public function test_platform_operator_without_context_is_denied(): void
    {
        $actor = $this->platformUser(['users.view', 'roles.view', 'teams.view']);
        foreach (['backoffice.users.index', 'backoffice.roles.index', 'backoffice.teams.index'] as $routeName) {
            $this->actingAs($actor)->withSession(['mfa.verified_at' => now()])->get(route($routeName))->assertForbidden();
        }
    }

    public function test_context_isolates_user_listing_and_creation(): void
    {
        $actor = $this->platformUser(['users.view', 'users.create', 'roles.assign', 'dashboard.view']);
        $municipality = Municipality::factory()->create();
        $other = Municipality::factory()->create();
        $visible = User::factory()->create(['municipality_id' => $municipality->id, 'name' => 'Utilizador Visível']);
        $hidden = User::factory()->create(['municipality_id' => $other->id, 'name' => 'Utilizador Oculto']);
        $role = $this->municipalRole($municipality, 'context_user_role', ['dashboard.view']);
        $actorRoleIds = $actor->roles()->pluck('roles.id')->all();
        $assignmentCount = PlatformOperatorAssignment::query()->where('user_id', $actor->id)->count();
        $entitlementCount = MunicipalityFeatureEntitlement::query()->count();

        $this->actingAs($actor)->withSession($this->contextSession($municipality))
            ->get(route('backoffice.users.index'))->assertOk()->assertSee($visible->name)->assertDontSee($hidden->name);

        $this->actingAs($actor)->withSession($this->contextSession($municipality))
            ->get(route('backoffice.users.show', $hidden))->assertForbidden();

        $this->actingAs($actor)->withSession($this->contextSession($municipality))
            ->post(route('backoffice.users.store'), [
                'name' => 'Novo Técnico Contextual',
                'email' => 'novo-tecnico-contextual@example.test',
                'role' => $role->name,
                'status' => 'active',
                'mfa_required' => true,
                'justification' => 'Criação autorizada no Município selecionado.',
            ])->assertRedirect();

        $created = User::query()->where('email', 'novo-tecnico-contextual@example.test')->sole();
        $this->assertSame($municipality->id, $created->municipality_id);
        $this->assertTrue($created->hasRole($role->name));
        $actor->refresh();
        $this->assertNull($actor->municipality_id);
        $this->assertSame($actorRoleIds, $actor->roles()->pluck('roles.id')->all());
        $this->assertSame($assignmentCount, PlatformOperatorAssignment::query()->where('user_id', $actor->id)->count());
        $this->assertSame($entitlementCount, MunicipalityFeatureEntitlement::query()->count());
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'user_created',
            'municipality_id' => $municipality->id,
            'target_user_id' => $created->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'user_created',
            'municipality_id' => $municipality->id,
            'subject_user_id' => $created->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function contextSession(Municipality $municipality): array
    {
        return [
            'mfa.verified_at' => now(),
            PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function municipalRole(
        Municipality $municipality,
        string $name,
        array $permissions,
    ): Role {
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => $name,
            'label' => 'Perfil municipal contextual',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(
            count($permissions),
            $permissionIds,
            'A fixture municipal pediu uma permission inexistente.',
        );

        $role->permissions()->sync($permissionIds->all());

        return $role;
    }
}
