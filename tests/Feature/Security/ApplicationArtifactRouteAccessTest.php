<?php

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\DocumentDossier;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationArtifactRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_artifact_routes_use_expected_permissions(): void
    {
        $expected = [
            'backoffice.applications.report.show'
                => 'permission:reports.view',

            'backoffice.applications.report.generate'
                => 'permission:reports.create,reports.export',

            'backoffice.application-reports.download'
                => 'permission:reports.view,reports.export',

            'backoffice.applications.document-dossier.show'
                => 'permission:documents.view',

            'backoffice.applications.document-dossier.generate'
                => 'permission:documents.create,documents.export',

            'backoffice.document-dossiers.download'
                => 'permission:documents.view',
        ];

        foreach ($expected as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route);

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
            );

            $this->assertContains($permissionMiddleware, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_reports_view_can_access_report_listing(): void
    {
        $user = $this->userWithCustomRole([
            'reports.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.applications.report.show', $application))
            ->assertOk();
    }

    public function test_user_without_reports_view_cannot_access_report_listing(): void
    {
        $user = $this->userWithCustomRole([
            'reports.create',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.applications.report.show', $application))
            ->assertForbidden();
    }

    public function test_custom_role_with_reports_create_reaches_report_generation(): void
    {
        $user = $this->userWithCustomRole([
            'reports.create',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('backoffice.applications.report.generate', $application),
                [
                    'format' => 'html',
                    'include_documents' => false,
                    'include_timeline' => false,
                    'include_internal_notes' => false,
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_custom_role_with_documents_view_can_access_dossier_listing(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'backoffice.applications.document-dossier.show',
                $application,
            ))
            ->assertOk();
    }

    public function test_documents_view_alone_cannot_generate_dossier(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $application = Application::factory()->create();

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.applications.document-dossier.generate',
                    $application,
                ),
                [
                    'include_rejected' => false,
                    'include_expired' => false,
                    'export_format' => 'html',
                ],
            )
            ->assertForbidden();
    }

    public function test_custom_role_with_documents_create_reaches_dossier_generation(): void
    {
        $user = $this->userWithCustomRole([
            'documents.create',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.applications.document-dossier.generate',
                    $application,
                ),
                [
                    'include_rejected' => false,
                    'include_expired' => false,
                    'export_format' => 'html',
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_candidate_cannot_access_report_listing_even_with_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'reports.view',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.applications.report.show', $application))
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_dossier_listing_even_with_permission(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'documents.view',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route(
                'backoffice.applications.document-dossier.show',
                $application,
            ))
            ->assertForbidden();
    }

    /**
     * @param list<string> $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'application_artifact_route_'.str()->random(8),
            'label' => 'Application artifact route test role',
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
     * @param list<string> $permissions
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
