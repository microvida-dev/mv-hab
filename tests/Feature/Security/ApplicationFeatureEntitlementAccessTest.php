<?php

namespace Tests\Feature\Security;

use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Permission;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class ApplicationFeatureEntitlementAccessTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
    }

    public function test_review_access_requires_feature_permission_and_record_scope(): void
    {
        $municipalityA = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $municipalityB = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $applicationA = $this->applicationForMunicipality($municipalityA->id);
        $applicationB = $this->applicationForMunicipality($municipalityB->id);

        $authorized = $this->userWithPermissions($municipalityA->id, ['applications.view']);
        $withoutFeature = $this->userWithPermissions(
            $this->municipalityWithFeatures([FeatureKey::ApplicationIntake])->id,
            ['applications.view'],
        );
        $withoutPermission = $this->userWithPermissions($municipalityA->id, ['dashboard.view']);

        $this->getAs($authorized, route('backoffice.applications.show', $applicationA))->assertOk();
        $this->getAs($withoutFeature, route('backoffice.applications.show', $applicationA))->assertForbidden();
        $this->getAs($withoutPermission, route('backoffice.applications.show', $applicationA))->assertForbidden();
        $this->getAs($authorized, route('backoffice.applications.show', $applicationB))->assertForbidden();
    }

    public function test_intake_policy_blocks_cross_municipality_record_even_with_feature_and_permission(): void
    {
        $municipalityA = $this->municipalityWithFeatures([FeatureKey::ApplicationIntake]);
        $municipalityB = $this->municipalityWithFeatures([FeatureKey::ApplicationIntake]);
        $applicationB = $this->applicationForMunicipality($municipalityB->id);
        $user = $this->userWithPermissions($municipalityA->id, ['administrative_processes.create']);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.application-intake.create-process', $applicationB))
            ->assertForbidden();

        $this->assertDatabaseCount('administrative_processes', 0);
    }

    public function test_application_export_requires_feature_permission_and_municipal_scope(): void
    {
        $municipalityA = $this->municipalityWithFeatures(FeatureKey::cases());
        $municipalityB = $this->municipalityWithFeatures(FeatureKey::cases());
        $report = ReportDefinition::query()->where('code', 'application_status_summary')->firstOrFail();
        $permissions = ['reports.view', 'reports.export', 'applications.view', 'applications.export'];
        $userA = $this->userWithPermissions($municipalityA->id, $permissions);
        $userB = $this->userWithPermissions($municipalityB->id, $permissions);
        $withoutExportFeatureMunicipality = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $withoutExportFeature = $this->userWithPermissions(
            $withoutExportFeatureMunicipality->id,
            $permissions,
        );
        $this->applicationForMunicipality($municipalityA->id);
        $this->applicationForMunicipality($municipalityB->id);

        $this->actingAs($withoutExportFeature)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.exports.store', $report), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertForbidden();

        $this->actingAs($userA)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.exports.store', $report), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertRedirect();

        $export = ReportExport::query()->firstOrFail();
        $this->assertSame($municipalityA->id, $export->run->filters['municipality_id']);
        $this->assertSame(1, $export->run->row_count);

        $this->actingAs($userA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.exports.show', $export))
            ->assertOk();

        $this->actingAs($userB)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.exports.download', $export))
            ->assertForbidden();

        app(MunicipalityEntitlementService::class)->disableFor(
            $municipalityA,
            FeatureKey::ApplicationExport,
            $userA,
            'Desativação para validar a proteção de exportações existentes.',
        );

        $this->actingAs($userA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.exports.show', $export))
            ->assertForbidden();

        $this->actingAs($userA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.exports.download', $export))
            ->assertForbidden();
    }

    public function test_export_feature_does_not_replace_domain_permission_and_mfa(): void
    {
        $municipality = $this->municipalityWithFeatures(FeatureKey::cases());
        $report = ReportDefinition::query()->where('code', 'application_status_summary')->firstOrFail();
        $withoutDomainPermission = $this->userWithPermissions($municipality->id, [
            'reports.view',
            'reports.export',
            'applications.view',
        ]);
        $withSensitivePermission = $this->userWithPermissions($municipality->id, [
            'reports.view',
            'reports.export',
            'applications.view',
            'applications.export',
        ]);

        $this->actingAs($withoutDomainPermission)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.reports.exports.store', $report), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertForbidden();

        session()->forget('mfa.verified_at');

        $this->actingAs($withSensitivePermission)
            ->post(route('backoffice.reports.exports.store', $report), [
                'format' => 'csv',
                'scope' => ExportScope::Aggregated->value,
            ])
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    public function test_inactive_role_does_not_grant_effective_permission(): void
    {
        $municipality = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $application = $this->applicationForMunicipality($municipality->id);
        $user = $this->userWithPermissions($municipality->id, ['applications.view'], false);

        $this->getAs($user, route('backoffice.applications.show', $application))->assertForbidden();
    }

    private function applicationForMunicipality(int $municipalityId): Application
    {
        $program = Program::factory()->create(['municipality_id' => $municipalityId]);
        $contest = Contest::factory()->create(['program_id' => $program->id]);

        return Application::factory()->submitted()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);
    }

    private function getAs(User $user, string $uri): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($uri);
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(int $municipalityId, array $permissions, bool $activeRole = true): User
    {
        $role = Role::query()->create([
            'name' => 'application_feature_'.str()->random(10),
            'label' => 'Teste de acesso a candidaturas',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => $activeRole,
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
