<?php

namespace Tests\Feature\Backoffice;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MunicipalityFeatureManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_management_routes_are_permission_first_and_have_all_backoffice_guards(): void
    {
        $expected = [
            'backoffice.platform.municipality-features.index' => 'permission:municipality_features.view',
            'backoffice.platform.municipality-features.show' => 'permission:municipality_features.view',
            'backoffice.platform.municipality-features.enable' => 'permission:municipality_features.update',
            'backoffice.platform.municipality-features.disable' => 'permission:municipality_features.update',
            'backoffice.platform.municipality-features.audit' => 'permission:municipality_features.audit',
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

    public function test_platform_administrator_can_view_enable_and_disable_features(): void
    {
        $administrator = $this->userWithRole('administrator');
        PlatformOperatorAssignment::factory()->for($administrator)->create();
        $municipality = Municipality::factory()->create();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipality-features.index'))
            ->assertOk()
            ->assertSee($municipality->name);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.enable', [
                $municipality,
                FeatureKey::ApplicationIntake,
            ]), [
                'justification' => 'Ativação aprovada pela plataforma.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('municipality_feature_entitlements', [
            'municipality_id' => $municipality->id,
            'feature_key' => FeatureKey::ApplicationIntake->value,
            'enabled' => true,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipality-features.show', $municipality))
            ->assertOk()
            ->assertSee('Recolha de candidaturas')
            ->assertSee('Desativar');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.disable', [
                $municipality,
                FeatureKey::ApplicationIntake,
            ]), [
                'justification' => 'Desativação aprovada pela plataforma.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('municipality_feature_entitlements', [
            'municipality_id' => $municipality->id,
            'feature_key' => FeatureKey::ApplicationIntake->value,
            'enabled' => false,
        ]);
    }

    public function test_justification_is_required_and_manipulated_payload_cannot_change_another_municipality(): void
    {
        $administrator = $this->userWithRole('administrator');
        PlatformOperatorAssignment::factory()->for($administrator)->create();
        $target = Municipality::factory()->create();
        $other = Municipality::factory()->create();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.enable', [
                $target,
                FeatureKey::ApplicationIntake,
            ]), [])
            ->assertSessionHasErrors('justification');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.enable', [
                $target,
                FeatureKey::ApplicationIntake,
            ]), [
                'municipality_id' => $other->id,
                'feature_key' => FeatureKey::ApplicationExport->value,
                'enabled' => false,
                'justification' => 'Ativação apenas para o Município da rota.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('municipality_feature_entitlements', [
            'municipality_id' => $target->id,
            'feature_key' => FeatureKey::ApplicationIntake->value,
            'enabled' => true,
        ]);
        $this->assertDatabaseMissing('municipality_feature_entitlements', [
            'municipality_id' => $other->id,
        ]);
    }

    public function test_municipal_administrator_candidate_auditor_and_user_without_permission_cannot_mutate(): void
    {
        $municipality = Municipality::factory()->create();
        $municipalAdministrator = $this->userWithRole('administrator', $municipality);
        $candidate = $this->userWithRole('candidate');
        $auditor = $this->userWithRole('auditor');
        $support = $this->userWithRole('support_agent');

        PlatformOperatorAssignment::factory()->for($municipalAdministrator)->create();
        PlatformOperatorAssignment::factory()->for($auditor)->create();
        $this->grantPermissions($candidate, 'candidate_feature_probe', ['municipality_features.view']);
        $this->grantPermissions($auditor, 'auditor_feature_probe', [
            'municipality_features.view',
            'municipality_features.update',
            'municipality_features.audit',
        ]);

        $this->actingAs($municipalAdministrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipality-features.show', $municipality))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipality-features.index'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.enable', [
                $municipality,
                FeatureKey::ApplicationIntake,
            ]), [
                'justification' => 'Tentativa de alteração pelo auditor.',
            ])
            ->assertForbidden();

        $this->actingAs($support)
            ->get(route('backoffice.platform.municipality-features.index'))
            ->assertForbidden();
    }

    public function test_unknown_feature_is_rejected(): void
    {
        $administrator = $this->userWithRole('administrator');
        PlatformOperatorAssignment::factory()->for($administrator)->create();
        $municipality = Municipality::factory()->create();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipality-features.enable', [
                $municipality,
                'applications.unknown',
            ]), [
                'justification' => 'Tentativa com chave desconhecida.',
            ])
            ->assertNotFound();
    }

    private function userWithRole(string $role, ?Municipality $municipality = null): User
    {
        $user = User::factory()->create([
            'municipality_id' => $municipality?->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /** @param list<string> $permissions */
    private function grantPermissions(User $user, string $name, array $permissions): void
    {
        $role = Role::query()->create([
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all(),
        );
        $user->roles()->attach($role);
    }
}
