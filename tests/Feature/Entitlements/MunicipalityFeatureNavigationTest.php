<?php

namespace Tests\Feature\Entitlements;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Services\Dashboard\DashboardDeadlineService;
use App\Services\Dashboard\DashboardMetricService;
use App\Services\Dashboard\DashboardQuickActionService;
use App\Services\Dashboard\DashboardWidgetRegistry;
use App\Services\Navigation\WorkspaceService;
use App\Services\Search\UniversalSearchService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class MunicipalityFeatureNavigationTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_review_navigation_requires_feature_and_permission(): void
    {
        $enabledMunicipality = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $disabledMunicipality = $this->municipalityWithFeatures([FeatureKey::ApplicationIntake]);
        $enabledUser = $this->userWithPermissions($enabledMunicipality->id, ['applications.view', 'documents.view']);
        $disabledUser = $this->userWithPermissions($disabledMunicipality->id, ['applications.view', 'documents.view']);
        $withoutPermission = $this->userWithPermissions($enabledMunicipality->id, ['dashboard.view']);
        $enabledUser->assignRole('municipal_technician');
        $disabledUser->assignRole('municipal_technician');

        $this->assertNotNull(app(WorkspaceService::class)->findVisibleItemByRoute(
            $enabledUser,
            'backoffice.applications.index',
        ));
        $this->assertNull(app(WorkspaceService::class)->findVisibleItemByRoute(
            $disabledUser,
            'backoffice.applications.index',
        ));
        $this->assertNull(app(WorkspaceService::class)->findVisibleItemByRoute(
            $withoutPermission,
            'backoffice.applications.index',
        ));

        $enabledActions = collect(app(DashboardQuickActionService::class)->forUser($enabledUser));
        $disabledActions = collect(app(DashboardQuickActionService::class)->forUser($disabledUser));

        $this->assertTrue($enabledActions->contains('route', 'backoffice.applications.index'));
        $this->assertFalse($disabledActions->contains('route', 'backoffice.applications.index'));
    }

    public function test_search_returns_only_authorized_municipal_applications(): void
    {
        $municipalityA = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $municipalityB = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $user = $this->userWithPermissions($municipalityA->id, ['applications.view']);
        $applicationA = $this->application($municipalityA->id, 'CAND-45D-AUTHORIZED');
        $this->application($municipalityB->id, 'CAND-45D-HIDDEN');

        $result = app(UniversalSearchService::class)->search($user, '45D');
        $labels = collect($result['groups'])
            ->flatMap(fn (array $group): array => $group['results'])
            ->pluck('label');

        $this->assertContains('Candidatura '.$applicationA->application_number, $labels);
        $this->assertNotContains('Candidatura CAND-45D-HIDDEN', $labels);
    }

    public function test_dashboard_application_counts_are_scoped_to_user_municipality(): void
    {
        $municipalityA = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $municipalityB = $this->municipalityWithFeatures([
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        ]);
        $user = $this->userWithPermissions($municipalityA->id, [
            'dashboard.view',
            'applications.view',
            'documents.view',
        ]);
        $user->assignRole('municipal_technician');

        $applicationA = $this->application($municipalityA->id, 'CAND-45D-METRIC-A');
        $applicationB = $this->application($municipalityB->id, 'CAND-45D-METRIC-B');
        DocumentSubmission::factory()->create([
            'application_id' => $applicationA->id,
            'status' => 'submitted',
        ]);
        DocumentSubmission::factory()->create([
            'application_id' => $applicationB->id,
            'status' => 'submitted',
        ]);

        $metrics = collect(app(DashboardMetricService::class)->forUser($user))->keyBy('key');
        $deadlines = collect(app(DashboardDeadlineService::class)->forUser($user))->keyBy('key');
        $widgets = collect(app(DashboardWidgetRegistry::class)->forUser($user))->keyBy('key');

        $this->assertSame(1, $metrics->get('pending_applications')['value']);
        $this->assertSame(1, $metrics->get('pending_documents')['value']);
        $this->assertSame(1, $deadlines->get('pending_documents')['count']);
        $this->assertSame(1, $widgets->get('technical_review')['value']);
    }

    private function application(int $municipalityId, string $number): Application
    {
        $program = Program::factory()->create(['municipality_id' => $municipalityId]);
        $contest = Contest::factory()->create(['program_id' => $program->id]);

        return Application::factory()->submitted()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'application_number' => $number,
        ]);
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(int $municipalityId, array $permissions): User
    {
        $role = Role::query()->create([
            'name' => 'navigation_feature_'.str()->random(10),
            'label' => 'Teste de navegação municipal',
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
