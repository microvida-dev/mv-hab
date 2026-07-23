<?php

namespace Tests\Feature\Security;

use App\Enums\FeatureKey;
use App\Models\ApplicationReport;
use App\Models\DocumentDossier;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ApplicationArtifactPolicyTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_custom_role_with_reports_view_can_view_and_download_reports(): void
    {
        $user = $this->userWithCustomRole([
            'reports.view',
        ]);

        $report = ApplicationReport::factory()->create();
        $report->application->program()->update([
            'municipality_id' => $user->municipality_id,
        ]);

        $this->assertTrue(
            $user->can('viewAny', ApplicationReport::class),
        );

        $this->assertTrue(
            $user->can('download', $report),
        );
    }

    public function test_custom_role_with_reports_create_can_generate_reports(): void
    {
        $user = $this->userWithCustomRole([
            'reports.create',
        ]);

        $this->assertTrue(
            $user->can('create', ApplicationReport::class),
        );
    }

    public function test_custom_role_with_reports_export_can_generate_reports(): void
    {
        $user = $this->userWithCustomRole([
            'reports.export',
        ]);

        $this->assertTrue(
            $user->can('create', ApplicationReport::class),
        );
    }

    public function test_candidate_cannot_access_reports_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'reports.view',
                'reports.create',
                'reports.export',
            ],
        );

        $report = ApplicationReport::factory()->create();

        $this->assertFalse(
            $user->can('viewAny', ApplicationReport::class),
        );

        $this->assertFalse(
            $user->can('create', ApplicationReport::class),
        );

        $this->assertFalse(
            $user->can('download', $report),
        );
    }

    public function test_custom_role_with_documents_view_can_view_and_download_dossiers(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $dossier = DocumentDossier::factory()->create();

        $this->assertTrue(
            $user->can('viewAny', DocumentDossier::class),
        );

        $this->assertTrue(
            $user->can('download', $dossier),
        );
    }

    public function test_custom_role_with_documents_create_can_generate_dossiers(): void
    {
        $user = $this->userWithCustomRole([
            'documents.create',
        ]);

        $this->assertTrue(
            $user->can('create', DocumentDossier::class),
        );
    }

    public function test_custom_role_with_documents_export_can_generate_dossiers(): void
    {
        $user = $this->userWithCustomRole([
            'documents.export',
        ]);

        $this->assertTrue(
            $user->can('create', DocumentDossier::class),
        );
    }

    public function test_documents_view_alone_cannot_generate_dossiers(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $this->assertFalse(
            $user->can('create', DocumentDossier::class),
        );
    }

    public function test_candidate_cannot_access_dossiers_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'documents.view',
                'documents.create',
                'documents.export',
            ],
        );

        $dossier = DocumentDossier::factory()->create();

        $this->assertFalse(
            $user->can('viewAny', DocumentDossier::class),
        );

        $this->assertFalse(
            $user->can('create', DocumentDossier::class),
        );

        $this->assertFalse(
            $user->can('download', $dossier),
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationExport);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'application_artifact_'.str()->random(8),
            'label' => 'Application artifact test role',
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
        $municipality = $this->municipalityWithFeatures(FeatureKey::ApplicationIntake, FeatureKey::ApplicationExport);
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
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
