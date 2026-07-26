<?php

namespace Tests\Feature\Security;

use App\Models\HousingUnit;
use App\Models\HousingUnitImage;
use App\Models\HousingUnitPublicDocument;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicHousingUnitPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_public_housing_unit_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.public-portal.housing-units.edit' => 'permission:housing_units.update',

            'backoffice.public-portal.housing-units.update' => 'permission:housing_units.update',

            'backoffice.public-portal.housing-units.publish' => 'permission:housing_units.update',

            'backoffice.public-portal.housing-units.unpublish' => 'permission:housing_units.update',

            'backoffice.public-portal.housing-units.preview' => 'permission:housing_units.view',

            'backoffice.public-portal.housing-units.images.store' => 'permission:housing_units.update',

            'backoffice.public-portal.images.update' => 'permission:housing_units.update',

            'backoffice.public-portal.images.destroy' => 'permission:housing_units.update',

            'backoffice.public-portal.housing-units.documents.store' => 'permission:housing_units.update',

            'backoffice.public-portal.documents.update' => 'permission:housing_units.update',

            'backoffice.public-portal.documents.destroy' => 'permission:housing_units.update',
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

    public function test_user_with_view_permission_can_preview_public_housing_unit(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.view',
        ]);

        $housingUnit = $this->publicHousingUnit();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.preview',
                $housingUnit,
            ))
            ->assertOk();
    }

    public function test_view_permission_does_not_grant_public_profile_mutation_access(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.view',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.edit',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-portal.housing-units.publish',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-portal.housing-units.unpublish',
                $housingUnit,
            ))
            ->assertForbidden();
    }

    public function test_user_with_update_permission_can_access_public_profile_editor(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.update',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.edit',
                $housingUnit,
            ))
            ->assertOk();
    }

    public function test_user_without_housing_unit_permissions_is_blocked(): void
    {
        $user = $this->userWithCustomRole([]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.preview',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.edit',
                $housingUnit,
            ))
            ->assertForbidden();
    }

    public function test_candidate_is_blocked_even_with_housing_unit_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'housing_units.view',
            'housing_units.update',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.preview',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.edit',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-portal.housing-units.publish',
                $housingUnit,
            ))
            ->assertForbidden();
    }

    public function test_auditor_can_preview_but_cannot_modify_public_housing_content(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'housing_units.view',
            'housing_units.update',
        ]);

        $housingUnit = $this->publicHousingUnit();

        $image = HousingUnitImage::factory()
            ->for($housingUnit)
            ->create();

        $document = HousingUnitPublicDocument::factory()
            ->for($housingUnit)
            ->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.preview',
                $housingUnit,
            ))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-portal.housing-units.edit',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-portal.housing-units.publish',
                $housingUnit,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route(
                'backoffice.public-portal.images.destroy',
                $image,
            ))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route(
                'backoffice.public-portal.documents.destroy',
                $document,
            ))
            ->assertForbidden();

        $this->assertDatabaseHas('housing_unit_images', [
            'id' => $image->id,
        ]);

        $this->assertDatabaseHas('housing_unit_public_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_update_permission_authorizes_public_images_and_documents_without_documents_permission(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.update',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $image = HousingUnitImage::factory()
            ->for($housingUnit)
            ->create();

        $document = HousingUnitPublicDocument::factory()
            ->for($housingUnit)
            ->create();

        $this->assertFalse($user->hasPermission('documents.view'));
        $this->assertFalse($user->hasPermission('documents.create'));
        $this->assertFalse($user->hasPermission('documents.update'));
        $this->assertFalse($user->hasPermission('documents.delete'));

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updatePublicProfileBackoffice',
                $housingUnit,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'publishPublicProfileBackoffice',
                $housingUnit,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'createBackoffice',
                HousingUnitImage::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $image,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $image,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'createBackoffice',
                HousingUnitPublicDocument::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $document,
            ),
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $document,
            ),
        );
    }

    public function test_view_permission_does_not_authorize_image_or_document_mutations(): void
    {
        $user = $this->userWithCustomRole([
            'housing_units.view',
        ]);

        $housingUnit = HousingUnit::factory()->create();

        $image = HousingUnitImage::factory()
            ->for($housingUnit)
            ->create();

        $document = HousingUnitPublicDocument::factory()
            ->for($housingUnit)
            ->create();

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'createBackoffice',
                HousingUnitImage::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $image,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $image,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'createBackoffice',
                HousingUnitPublicDocument::class,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'updateBackoffice',
                $document,
            ),
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'deleteBackoffice',
                $document,
            ),
        );
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
            'name' => 'public_housing_'.str()->random(8),
            'label' => 'Public housing permission test role',
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicHousingUnit(array $overrides = []): HousingUnit
    {
        return HousingUnit::factory()->create(array_merge([
            'public_title' => 'Habitação pública de teste',
            'public_slug' => 'habitacao-publica-'.fake()->unique()->numerify('######'),
            'public_summary' => 'Resumo público de teste.',
        ], $overrides));
    }
}
