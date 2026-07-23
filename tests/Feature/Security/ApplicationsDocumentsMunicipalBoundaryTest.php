<?php

namespace Tests\Feature\Security;

use App\Enums\FeatureKey;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\HousingApplication;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ApplicationsDocumentsMunicipalBoundaryTest extends TestCase
{
    use InteractsWithMunicipalFeatures, RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = $this->municipalityWithFeatures(FeatureKey::ApplicationReview);
        $this->municipalityB = $this->municipalityWithFeatures(FeatureKey::ApplicationReview);
    }

    public function test_exact_permissions_only_expose_local_applications_and_documents(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['applications.view', 'documents.view'],
        );
        $localApplication = HousingApplication::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $foreignApplication = HousingApplication::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $localDocument = Document::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'housing_application_id' => $localApplication->id,
        ]);
        $foreignDocument = Document::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'housing_application_id' => $foreignApplication->id,
        ]);

        $this->getAs($actor, route('applications.show', $localApplication))->assertOk();
        $this->getAs($actor, route('documents.show', $localDocument))->assertOk();
        $this->getAs($actor, route('applications.show', $foreignApplication))->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->getJson(route('documents.show', $foreignDocument))
            ->assertForbidden()
            ->assertJsonPath('code', 'access_denied')
            ->assertJsonMissing(['path' => $foreignDocument->path]);
    }

    public function test_private_document_path_and_mutating_actions_are_not_exposed_to_reader(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['documents.view']);
        $privatePath = 'documents/private/'.fake()->uuid().'/identity.pdf';
        $document = Document::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'path' => $privatePath,
        ]);

        $this->getAs($actor, route('documents.show', $document))
            ->assertOk()
            ->assertDontSee($privatePath)
            ->assertDontSee(route('documents.edit', $document), false);
    }

    public function test_feature_permission_and_mfa_are_independent_guards(): void
    {
        $municipalityWithoutReview = Municipality::factory()->create();
        $application = HousingApplication::factory()->create([
            'municipality_id' => $municipalityWithoutReview->id,
        ]);
        $withoutFeature = $this->userWithPermissions(
            $municipalityWithoutReview,
            ['applications.view'],
        );
        $withoutPermission = $this->userWithPermissions(
            $this->municipalityA,
            ['documents.view'],
        );
        $mfaRequired = $this->userWithPermissions(
            $this->municipalityA,
            ['applications.view'],
            mfaRequired: true,
        );
        $localApplication = HousingApplication::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);

        $this->getAs($withoutFeature, route('applications.show', $application))
            ->assertForbidden();
        $this->getAs($withoutPermission, route('applications.show', $localApplication))
            ->assertForbidden();

        session()->forget('mfa.verified_at');

        $this->actingAs($mfaRequired)
            ->get(route('applications.show', $localApplication))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    public function test_candidate_auditor_and_inactive_access_remain_fail_closed(): void
    {
        $application = HousingApplication::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $document = Document::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'housing_application_id' => $application->id,
        ]);
        $candidate = $this->userWithSystemRole(
            'candidate',
            $this->municipalityA,
            ['applications.view', 'documents.view'],
        );
        $auditor = $this->userWithSystemRole(
            'auditor',
            $this->municipalityA,
            ['applications.view', 'applications.delete', 'documents.view', 'documents.delete'],
        );
        $inactiveRole = $this->userWithPermissions(
            $this->municipalityA,
            ['applications.view'],
            activeRole: false,
        );
        $inactiveUser = $this->userWithPermissions(
            $this->municipalityA,
            ['applications.view'],
            userStatus: 'inactive',
        );

        $this->getAs($candidate, route('applications.show', $application))->assertForbidden();
        $this->getAs($auditor, route('applications.show', $application))->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->deleteJson(route('documents.destroy', $document))
            ->assertForbidden();

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
        $this->getAs($inactiveRole, route('applications.show', $application))->assertForbidden();
        $this->getAs($inactiveUser, route('applications.show', $application))->assertForbidden();
    }

    public function test_cross_municipality_delete_is_denied_without_side_effects(): void
    {
        $actor = $this->userWithPermissions(
            $this->municipalityA,
            ['documents.delete'],
        );
        $foreignDocument = Document::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->deleteJson(route('documents.destroy', $foreignDocument))
            ->assertForbidden();

        $this->assertDatabaseHas('documents', ['id' => $foreignDocument->id]);
    }

    public function test_document_create_permission_can_create_only_local_template_versions(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['documents.create']);
        $localTemplate = DocumentTemplate::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $foreignTemplate = DocumentTemplate::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $payload = [
            'title' => 'Versão municipal controlada',
            'body' => 'Conteúdo privado da versão municipal para validação de acesso.',
        ];

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('backoffice.document-template-versions.store', $localTemplate),
                $payload,
            )
            ->assertRedirect();

        $this->assertDatabaseHas('document_template_versions', [
            'document_template_id' => $localTemplate->id,
            'title' => 'Versão municipal controlada',
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->postJson(
                route('backoffice.document-template-versions.store', $foreignTemplate),
                $payload,
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('document_template_versions', [
            'document_template_id' => $foreignTemplate->id,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(
        Municipality $municipality,
        array $permissions,
        bool $activeRole = true,
        bool $mfaRequired = false,
        string $userStatus = 'active',
    ): User {
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'sprint_47b_'.str()->random(12),
            'label' => 'Teste 47B',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $activeRole,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => $userStatus,
            'mfa_required' => $mfaRequired,
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithSystemRole(
        string $roleName,
        Municipality $municipality,
        array $permissions,
    ): User {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);

        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function getAs(User $user, string $url): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($url);
    }
}
