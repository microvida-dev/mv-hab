<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportingCommunicationsNotificationsConfigRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_new_permissions_exist_and_administrator_keeps_only_wildcard(): void
    {
        $newPermissions = $this->manifestPermissions('new');

        $this->assertCount(57, $newPermissions);
        $this->assertSame(
            57,
            Permission::query()->whereIn('name', $newPermissions)->count(),
        );
        $this->assertSame(
            ['*'],
            $this->rolePermissions('administrator'),
        );
    }

    public function test_candidate_receives_no_new_backoffice_permission(): void
    {
        $this->assertSame(
            [],
            array_values(array_intersect(
                $this->rolePermissions('candidate'),
                $this->manifestPermissions('new'),
            )),
        );
    }

    public function test_auditor_has_no_manifest_mutation_permission(): void
    {
        $auditorPermissions = $this->rolePermissions('auditor');
        $mutationPermissions = $this->manifestMutationPermissions();

        $this->assertSame(
            [],
            array_values(array_intersect(
                $auditorPermissions,
                $mutationPermissions,
            )),
        );
        $this->assertNotContains('reports.export', $auditorPermissions);
        $this->assertNotContains(
            'communications.deliveries.resend',
            $auditorPermissions,
        );
        $this->assertNotContains(
            'communications.cancel',
            $auditorPermissions,
        );
    }

    public function test_operational_roles_are_least_privilege(): void
    {
        $support = $this->rolePermissions('support_agent');
        $financial = $this->rolePermissions('financial_manager');
        $housing = $this->rolePermissions('housing_manager');
        $legal = $this->rolePermissions('legal_manager');
        $technician = $this->rolePermissions('municipal_technician');

        $this->assertPermissions(
            $support,
            [
                'communications.create',
                'communications.view',
                'notification_preferences.view',
                'notifications.create',
                'notifications.view',
                'support.assign',
                'support.attachments.download',
                'support.message',
                'support.resolve',
            ],
        );
        $this->assertPermissionsAbsent(
            $support,
            [
                'communication_variables.create',
                'dashboard_definitions.create',
                'notification_templates.create',
                'report_definitions.create',
                'reports.export',
                'reports.run',
                'settings.update',
            ],
        );

        $this->assertPermissions(
            $financial,
            [
                'reports.export',
                'reports.export_financial',
                'reports.run',
                'reports.view',
                'reports.view_financial',
            ],
        );
        $this->assertPermissionsAbsent(
            $financial,
            [
                'communication_variables.create',
                'communications.archive',
                'communications.cancel',
                'notification_templates.create',
                'settings.update',
            ],
        );

        $this->assertPermissions(
            $housing,
            [
                'communications.create',
                'reports.export',
                'reports.run',
                'reports.view_maintenance',
            ],
        );
        $this->assertPermissionsAbsent(
            $housing,
            [
                'communication_variables.create',
                'dashboard_definitions.create',
                'notification_templates.create',
                'report_definitions.create',
                'settings.update',
            ],
        );

        $this->assertPermissions(
            $legal,
            [
                'communications.create',
                'documents.download',
                'reports.export',
                'reports.run',
            ],
        );
        $this->assertPermissionsAbsent(
            $legal,
            [
                'communication_variables.create',
                'dashboard_definitions.create',
                'notification_templates.create',
                'settings.update',
            ],
        );

        $this->assertPermissionsAbsent(
            $technician,
            [
                'communication_variables.create',
                'communication_variables.update',
                'dashboard_definitions.create',
                'dashboard_definitions.delete',
                'dashboard_definitions.update',
                'indicator_definitions.create',
                'indicator_definitions.update',
                'report_definitions.create',
                'report_definitions.delete',
                'report_definitions.update',
                'settings.activate',
                'settings.deactivate',
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function rolePermissions(string $roleName): array
    {
        $permissions = Role::query()
            ->where('name', $roleName)
            ->firstOrFail()
            ->permissions()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return array_values(array_filter($permissions, 'is_string'));
    }

    /**
     * @return list<string>
     */
    private function manifestPermissions(string $origin): array
    {
        $manifest = $this->manifest();
        $permissions = $manifest['permissions'][$origin] ?? null;

        $this->assertIsArray($permissions);

        return array_values(array_filter($permissions, 'is_string'));
    }

    /**
     * @return list<string>
     */
    private function manifestMutationPermissions(): array
    {
        $routes = $this->manifest()['routes'] ?? null;
        $this->assertIsArray($routes);
        $permissions = [];

        foreach ($routes as $route) {
            $this->assertIsArray($route);

            if (($route['operation_type'] ?? null) !== 'mutation') {
                continue;
            }

            $permission = $route['resolved_permission'] ?? null;

            if (is_string($permission)) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $contents = file_get_contents(base_path(
            'docs/access/manifests/sprint-47h-route-manifest.json',
        ));

        $this->assertIsString($contents);
        $manifest = json_decode(
            $contents,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($manifest);

        return $manifest;
    }

    /**
     * @param  list<string>  $actual
     * @param  list<string>  $expected
     */
    private function assertPermissions(array $actual, array $expected): void
    {
        foreach ($expected as $permission) {
            $this->assertContains($permission, $actual);
        }
    }

    /**
     * @param  list<string>  $actual
     * @param  list<string>  $unexpected
     */
    private function assertPermissionsAbsent(
        array $actual,
        array $unexpected,
    ): void {
        foreach ($unexpected as $permission) {
            $this->assertNotContains($permission, $actual);
        }
    }
}
