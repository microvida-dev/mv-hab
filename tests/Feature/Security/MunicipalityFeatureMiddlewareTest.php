<?php

namespace Tests\Feature\Security;

use App\Enums\FeatureKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class MunicipalityFeatureMiddlewareTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth', 'municipality.feature:unknown.feature'])
            ->get('/_tests/municipality-feature/unknown', fn (): string => 'ok')
            ->name('tests.municipality-feature.unknown');
    }

    public function test_enabled_feature_allows_request_with_existing_permission(): void
    {
        $municipality = $this->municipalityWithFeatures([FeatureKey::ApplicationIntake]);
        $user = $this->userWithPermissions($municipality->id, ['administrative_processes.create']);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.application-intake.index'))
            ->assertOk();
    }

    public function test_disabled_feature_and_missing_municipality_fail_closed(): void
    {
        $municipality = $this->municipalityWithFeatures();
        $municipalUser = $this->userWithPermissions($municipality->id, ['administrative_processes.create']);
        $platformUser = $this->userWithPermissions(null, ['administrative_processes.create']);

        foreach ([$municipalUser, $platformUser] as $user) {
            $this->actingAs($user)
                ->withSession(['mfa.verified_at' => now()])
                ->get(route('backoffice.application-intake.index'))
                ->assertForbidden()
                ->assertSee('Esta funcionalidade não está disponível para o Município atual.');
        }
    }

    public function test_unknown_feature_is_rejected_without_exposing_configuration(): void
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::cases());
        $user = $this->userWithPermissions($municipality->id, ['dashboard.view']);

        $this->actingAs($user)
            ->get('/_tests/municipality-feature/unknown')
            ->assertNotFound();
    }

    public function test_feature_middleware_does_not_grant_permission(): void
    {
        $municipality = $this->municipalityWithFeatures([FeatureKey::ApplicationIntake]);
        $user = $this->userWithPermissions($municipality->id, ['dashboard.view']);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.application-intake.index'))
            ->assertForbidden();
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(?int $municipalityId, array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'feature_test_'.str()->random(10),
            'label' => 'Teste de funcionalidades',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));

        $user = User::factory()->create([
            'municipality_id' => $municipalityId,
            'status' => 'active',
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
