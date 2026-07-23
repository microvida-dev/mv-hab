<?php

namespace Tests\Feature\Security;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Access\RoleManagementService;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class PermissionSensitiveMfaTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_sensitive_permission_challenges_user_without_verified_mfa(): void
    {
        $user = $this->userWithCustomRole('analista_documental_mfa', [
            'documents.view',
            'documents.approve',
        ]);

        $this->assertTrue(app(MfaEnforcementService::class)->requiresMfa($user));

        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $this->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-reviews.index'))
            ->assertOk();
    }

    public function test_non_sensitive_permission_keeps_existing_access_without_mfa_challenge(): void
    {
        $user = $this->userWithCustomRole('consulta_documental_sem_mfa', ['documents.view']);

        $this->assertFalse(app(MfaEnforcementService::class)->requiresMfa($user));

        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertOk();
    }

    public function test_removing_last_sensitive_permission_recalculates_requirement_immediately(): void
    {
        $user = $this->userWithCustomRole('analista_mfa_recalculado', [
            'documents.view',
            'documents.reject',
        ]);
        $administrator = $this->administrator($user->municipality);
        $role = $user->roles()->where('name', 'analista_mfa_recalculado')->firstOrFail();
        $viewPermission = Permission::query()->where('name', 'documents.view')->firstOrFail();
        $mfa = app(MfaEnforcementService::class);

        $this->assertTrue($mfa->requiresMfa($user));

        app(RoleManagementService::class)->synchronizePermissions(
            $administrator,
            $role,
            [(int) $viewPermission->id],
            'Remover a última permissão sensível após revisão.',
        );

        $this->assertFalse($mfa->requiresMfa($user->refresh()));
        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertOk();
    }

    public function test_inactive_sensitive_role_does_not_require_mfa_or_grant_access(): void
    {
        $user = $this->userWithCustomRole('consulta_documental_ativa', ['documents.view']);
        $inactive = $this->customRole(
            'decisao_documental_inativa',
            ['documents.approve'],
            false,
            $user->municipality,
        );
        $user->roles()->attach($inactive);

        $this->assertFalse(app(MfaEnforcementService::class)->requiresMfa($user));
        $this->assertFalse($user->hasPermission('documents.approve'));

        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertOk();
    }

    public function test_exporter_template_requires_mfa_and_legacy_rules_remain_active(): void
    {
        $template = app(MunicipalRoleTemplateRegistry::class)->resolve('exportador-candidaturas');
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
        $role = $this->customRoleFromIds(
            'exportador_municipal_mfa',
            $template['permission_ids'],
            true,
            $municipality,
        );
        $exporter = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => false,
        ]);
        $exporter->roles()->attach($role);
        $support = $this->userWithSystemRole('support_agent');
        $technician = $this->userWithSystemRole('municipal_technician');
        $manual = $this->userWithCustomRole('consulta_manual_mfa', ['applications.view']);
        $manual->forceFill(['mfa_required' => true])->save();
        $mfa = app(MfaEnforcementService::class);

        $this->assertTrue($mfa->requiresMfa($exporter));
        $this->assertTrue($mfa->requiresMfa($technician));
        $this->assertTrue($mfa->requiresMfa($manual->refresh()));
        $this->assertFalse($mfa->requiresMfa($support));
    }

    /** @param list<string> $permissionNames */
    private function userWithCustomRole(string $name, array $permissionNames): User
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
        $role = $this->customRole($name, $permissionNames, true, $municipality);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => false,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    /** @param list<string> $permissionNames */
    private function customRole(
        string $name,
        array $permissionNames,
        bool $active = true,
        ?Municipality $municipality = null,
    ): Role {
        $ids = Permission::query()->whereIn('name', $permissionNames)->pluck('id')->all();

        return $this->customRoleFromIds(
            $name,
            array_map(fn ($id): int => (int) $id, $ids),
            $active,
            $municipality,
        );
    }

    /** @param list<int> $permissionIds */
    private function customRoleFromIds(
        string $name,
        array $permissionIds,
        bool $active = true,
        ?Municipality $municipality = null,
    ): Role {
        $municipality ??= Municipality::query()->first()
            ?? Municipality::factory()->create();
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $active,
        ]);
        $role->permissions()->sync($permissionIds);

        return $role;
    }

    private function userWithSystemRole(string $name, ?Municipality $municipality = null): User
    {
        $municipality ??= $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationReview);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => false,
        ]);
        $user->assignRole($name);

        return $user;
    }

    private function administrator(?Municipality $municipality = null): User
    {
        return $this->userWithSystemRole('administrator', $municipality);
    }
}
