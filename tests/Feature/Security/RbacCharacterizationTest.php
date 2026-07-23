<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Navigation\WorkspaceService;
use App\Services\Security\MfaEnforcementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_custom_roles_resolve_exact_module_action_and_global_wildcards(): void
    {
        $exact = $this->userWithCustomRole(
            roleName: 'custom_application_viewer',
            permissions: ['applications.view'],
        );

        $moduleWildcard = $this->userWithCustomRole(
            roleName: 'custom_document_operator',
            permissions: ['documents.*'],
        );

        $actionWildcard = $this->userWithCustomRole(
            roleName: 'custom_cross_module_viewer',
            permissions: ['*.view'],
        );

        $globalWildcard = $this->userWithCustomRole(
            roleName: 'custom_full_access',
            permissions: ['*'],
        );

        $this->assertTrue($exact->hasPermission('applications.view'));
        $this->assertFalse($exact->hasPermission('applications.update'));

        $this->assertTrue($moduleWildcard->hasPermission('documents.view'));
        $this->assertTrue($moduleWildcard->hasPermission('documents.approve'));
        $this->assertFalse($moduleWildcard->hasPermission('applications.view'));

        $this->assertTrue($actionWildcard->hasPermission('applications.view'));
        $this->assertTrue($actionWildcard->hasPermission('documents.view'));
        $this->assertFalse($actionWildcard->hasPermission('documents.approve'));

        $this->assertTrue($globalWildcard->hasPermission('settings.delete'));
        $this->assertTrue($globalWildcard->hasPermission('applications.export'));
    }

    public function test_permissions_are_aggregated_across_multiple_custom_roles(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assignCustomRole($user, 'application_intake_operator', [
            'applications.view',
            'applications.create',
            'applications.update',
        ]);

        $this->assignCustomRole($user, 'application_export_operator', [
            'reports.view',
            'reports.export',
            'exports.view',
            'exports.create',
            'exports.download',
        ]);

        $this->assertTrue($user->hasPermission('applications.view'));
        $this->assertTrue($user->hasPermission('applications.update'));
        $this->assertTrue($user->hasPermission('reports.export'));
        $this->assertTrue($user->hasPermission('exports.download'));
        $this->assertFalse($user->hasPermission('finance.view'));
    }

    public function test_custom_role_without_intake_permission_is_blocked_by_permission_middleware(): void
    {
        $user = $this->userWithCustomRole(
            roleName: 'application_intake_operator',
            permissions: [
                'applications.view',
                'administrative_processes.view',
            ],
        );

        $this->assertTrue($user->hasPermission('applications.view'));
        $this->assertTrue($user->hasPermission('administrative_processes.view'));

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.application-intake.index'))
            ->assertForbidden();
    }

    public function test_workspace_service_and_http_route_accept_authorized_custom_role(): void
    {
        $user = $this->userWithCustomRole(
            roleName: 'document_review_operator',
            permissions: ['dashboard.view', 'documents.view'],
        );

        $workspace = app(WorkspaceService::class)
            ->authorizedWorkspace($user, 'atendimento');

        $this->assertIsArray($workspace);
        $this->assertSame('atendimento', $workspace['key']);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('workspaces.show', 'atendimento'))
            ->assertOk();
    }

    public function test_mfa_is_triggered_by_sensitive_permissions_roles_or_manual_flag(): void
    {
        $mfa = app(MfaEnforcementService::class);

        $customSensitiveUser = $this->userWithCustomRole(
            roleName: 'custom_sensitive_document_reviewer',
            permissions: [
                'documents.view',
                'documents.approve',
                'applications.export',
                'reports.export_sensitive',
            ],
        );

        $this->assertTrue($mfa->requiresMfa($customSensitiveUser));

        $customSensitiveUser->forceFill(['mfa_required' => true])->save();

        $this->assertTrue($mfa->requiresMfa($customSensitiveUser->refresh()));

        $municipalTechnician = User::factory()->create(['status' => 'active']);
        $municipalTechnician->assignRole('municipal_technician');

        $this->assertTrue($mfa->requiresMfa($municipalTechnician));
    }

    public function test_application_intake_routes_exclude_fixed_role_and_use_permission_middleware(): void
    {
        $route = Route::getRoutes()
            ->getByName('backoffice.application-intake.index');

        $this->assertNotNull($route);

        $this->assertContains(
            'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
            $route->excludedMiddleware(),
        );

        $middleware = app('router')->resolveMiddleware(
            $route->gatherMiddleware(),
            $route->excludedMiddleware(),
        );

        $this->assertSame(
            [
                'web',
                'auth',
                'active.backoffice',
                'mfa.backoffice',
                'log.backoffice',
                'permission:administrative_processes.create',
            ],
            $middleware,
        );

        $this->assertFalse(
            collect($middleware)->contains(
                fn (string $item): bool => str_starts_with($item, 'role:')
            ),
        );
    }

    public function test_document_review_routes_keep_fixed_role_list_and_backoffice_guards(): void
    {
        $route = Route::getRoutes()->getByName('admin.document-reviews.index');

        $this->assertNotNull($route);

        $middleware = $route->gatherMiddleware();

        $this->assertContains(
            'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
            $middleware,
        );
        $this->assertContains('active.backoffice', $middleware);
        $this->assertContains('mfa.backoffice', $middleware);
        $this->assertContains('log.backoffice', $middleware);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(string $roleName, array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->assignCustomRole($user, $roleName, $permissions);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function assignCustomRole(User $user, string $roleName, array $permissions): Role
    {
        $role = Role::query()->create([
            'name' => $roleName,
            'label' => str($roleName)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): int {
                [$module, $action] = str_contains($permission, '.')
                    ? explode('.', $permission, 2)
                    : [$permission, $permission];

                return Permission::query()->firstOrCreate(
                    ['name' => $permission],
                    [
                        'module' => $module,
                        'action' => $action,
                        'description' => 'Permissão criada apenas para caracterização do RBAC.',
                    ],
                )->getKey();
            })
            ->values();

        $role->permissions()->sync($permissionIds);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $role;
    }
}
