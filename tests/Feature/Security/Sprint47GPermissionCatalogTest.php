<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionCatalogService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Sprint47GPermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const NEW_PERMISSION_DEFINITIONS = [
        'agenda.view' => ['agenda', 'view'],
        'inspections.attachments.create' => ['inspections', 'attachments.create'],
        'inspections.attachments.download' => ['inspections', 'attachments.download'],
        'inspections.cancel' => ['inspections', 'cancel'],
        'inspections.close' => ['inspections', 'close'],
        'inspections.complete' => ['inspections', 'complete'],
        'inspections.items.create' => ['inspections', 'items.create'],
        'inspections.items.update' => ['inspections', 'items.update'],
        'inspections.reports.cancel' => ['inspections', 'reports.cancel'],
        'inspections.reports.download' => ['inspections', 'reports.download'],
        'inspections.reports.generate' => ['inspections', 'reports.generate'],
        'inspections.reports.validate' => ['inspections', 'reports.validate'],
        'inspections.reports.view' => ['inspections', 'reports.view'],
        'inspections.start' => ['inspections', 'start'],
        'inspections.templates.create' => ['inspections', 'templates.create'],
        'inspections.templates.update' => ['inspections', 'templates.update'],
        'inspections.templates.view' => ['inspections', 'templates.view'],
        'inspections.validate' => ['inspections', 'validate'],
        'maintenance.assignments.cancel' => ['maintenance', 'assignments.cancel'],
        'maintenance.assignments.create' => ['maintenance', 'assignments.create'],
        'maintenance.attachments.create' => ['maintenance', 'attachments.create'],
        'maintenance.attachments.download' => ['maintenance', 'attachments.download'],
        'maintenance.categories.create' => ['maintenance', 'categories.create'],
        'maintenance.categories.delete' => ['maintenance', 'categories.delete'],
        'maintenance.categories.update' => ['maintenance', 'categories.update'],
        'maintenance.categories.view' => ['maintenance', 'categories.view'],
        'maintenance.costs.approve' => ['maintenance', 'costs.approve'],
        'maintenance.costs.create' => ['maintenance', 'costs.create'],
        'maintenance.costs.reject' => ['maintenance', 'costs.reject'],
        'maintenance.costs.view' => ['maintenance', 'costs.view'],
        'maintenance.interventions.cancel' => ['maintenance', 'interventions.cancel'],
        'maintenance.interventions.complete' => ['maintenance', 'interventions.complete'],
        'maintenance.interventions.create' => ['maintenance', 'interventions.create'],
        'maintenance.interventions.start' => ['maintenance', 'interventions.start'],
        'maintenance.interventions.view' => ['maintenance', 'interventions.view'],
        'maintenance.suppliers.create' => ['maintenance', 'suppliers.create'],
        'maintenance.suppliers.update' => ['maintenance', 'suppliers.update'],
        'maintenance.suppliers.view' => ['maintenance', 'suppliers.view'],
        'maintenance_requests.cancel' => ['maintenance_requests', 'cancel'],
        'maintenance_requests.close' => ['maintenance_requests', 'close'],
        'maintenance_requests.resolve' => ['maintenance_requests', 'resolve'],
        'maintenance_requests.review' => ['maintenance_requests', 'review'],
        'maintenance_requests.schedule' => ['maintenance_requests', 'schedule'],
        'maintenance_requests.start' => ['maintenance_requests', 'start'],
        'visits.availabilities.create' => ['visits', 'availabilities.create'],
        'visits.availabilities.delete' => ['visits', 'availabilities.delete'],
        'visits.availabilities.generate_slots' => ['visits', 'availabilities.generate_slots'],
        'visits.availabilities.update' => ['visits', 'availabilities.update'],
        'visits.availabilities.view' => ['visits', 'availabilities.view'],
        'visits.cancel' => ['visits', 'cancel'],
        'visits.complete' => ['visits', 'complete'],
        'visits.confirm' => ['visits', 'confirm'],
        'visits.mark_no_show' => ['visits', 'mark_no_show'],
        'visits.slots.block' => ['visits', 'slots.block'],
        'visits.slots.unblock' => ['visits', 'slots.unblock'],
        'visits.slots.view' => ['visits', 'slots.view'],
    ];

    private const EXPECTED_ROLE_PERMISSIONS = [
        'municipal_technician' => [
            'agenda.view',
            'inspections.attachments.create',
            'inspections.cancel',
            'inspections.close',
            'inspections.complete',
            'inspections.items.create',
            'inspections.items.update',
            'inspections.reports.cancel',
            'inspections.reports.generate',
            'inspections.reports.view',
            'inspections.start',
            'inspections.templates.view',
            'maintenance.assignments.cancel',
            'maintenance.assignments.create',
            'maintenance.attachments.create',
            'maintenance.categories.view',
            'maintenance.costs.create',
            'maintenance.costs.view',
            'maintenance.interventions.cancel',
            'maintenance.interventions.complete',
            'maintenance.interventions.create',
            'maintenance.interventions.start',
            'maintenance.interventions.view',
            'maintenance.suppliers.view',
            'maintenance_requests.cancel',
            'maintenance_requests.close',
            'maintenance_requests.resolve',
            'maintenance_requests.review',
            'maintenance_requests.schedule',
            'maintenance_requests.start',
            'visits.availabilities.create',
            'visits.availabilities.generate_slots',
            'visits.availabilities.update',
            'visits.availabilities.view',
            'visits.cancel',
            'visits.complete',
            'visits.confirm',
            'visits.mark_no_show',
            'visits.slots.block',
            'visits.slots.unblock',
            'visits.slots.view',
        ],
        'maintenance_manager' => [
            'agenda.view',
            'inspections.attachments.create',
            'inspections.attachments.download',
            'inspections.cancel',
            'inspections.close',
            'inspections.complete',
            'inspections.items.create',
            'inspections.items.update',
            'inspections.reports.cancel',
            'inspections.reports.download',
            'inspections.reports.generate',
            'inspections.reports.validate',
            'inspections.reports.view',
            'inspections.start',
            'inspections.templates.create',
            'inspections.templates.update',
            'inspections.templates.view',
            'inspections.validate',
            'maintenance.assignments.cancel',
            'maintenance.assignments.create',
            'maintenance.attachments.create',
            'maintenance.attachments.download',
            'maintenance.categories.create',
            'maintenance.categories.delete',
            'maintenance.categories.update',
            'maintenance.categories.view',
            'maintenance.costs.create',
            'maintenance.costs.view',
            'maintenance.interventions.cancel',
            'maintenance.interventions.complete',
            'maintenance.interventions.create',
            'maintenance.interventions.start',
            'maintenance.interventions.view',
            'maintenance.suppliers.create',
            'maintenance.suppliers.update',
            'maintenance.suppliers.view',
            'maintenance_requests.cancel',
            'maintenance_requests.close',
            'maintenance_requests.resolve',
            'maintenance_requests.review',
            'maintenance_requests.schedule',
            'maintenance_requests.start',
        ],
        'inspection_manager' => [
            'agenda.view',
            'inspections.attachments.create',
            'inspections.attachments.download',
            'inspections.cancel',
            'inspections.close',
            'inspections.complete',
            'inspections.items.create',
            'inspections.items.update',
            'inspections.reports.cancel',
            'inspections.reports.download',
            'inspections.reports.generate',
            'inspections.reports.validate',
            'inspections.reports.view',
            'inspections.start',
            'inspections.templates.create',
            'inspections.templates.update',
            'inspections.templates.view',
            'inspections.validate',
        ],
        'housing_manager' => [
            'agenda.view',
            'visits.availabilities.create',
            'visits.availabilities.delete',
            'visits.availabilities.generate_slots',
            'visits.availabilities.update',
            'visits.availabilities.view',
            'visits.cancel',
            'visits.complete',
            'visits.confirm',
            'visits.mark_no_show',
            'visits.slots.block',
            'visits.slots.unblock',
            'visits.slots.view',
        ],
        'support_agent' => [
            'agenda.view',
            'visits.availabilities.view',
            'visits.cancel',
            'visits.confirm',
            'visits.mark_no_show',
            'visits.slots.view',
        ],
        'financial_manager' => [
            'agenda.view',
            'maintenance.costs.approve',
            'maintenance.costs.reject',
            'maintenance.costs.view',
        ],
        'legal_manager' => [
            'agenda.view',
        ],
        'jury' => [
            'agenda.view',
        ],
        'auditor' => [
            'agenda.view',
            'inspections.reports.view',
            'inspections.templates.view',
            'maintenance.categories.view',
            'maintenance.costs.view',
            'maintenance.interventions.view',
            'maintenance.suppliers.view',
            'visits.availabilities.view',
            'visits.slots.view',
        ],
        'candidate' => [
        ],
    ];

    private const MUTATION_PERMISSIONS = [
        'inspections.attachments.create',
        'inspections.cancel',
        'inspections.close',
        'inspections.complete',
        'inspections.items.create',
        'inspections.items.update',
        'inspections.reports.cancel',
        'inspections.reports.generate',
        'inspections.reports.validate',
        'inspections.start',
        'inspections.templates.create',
        'inspections.templates.update',
        'inspections.validate',
        'maintenance.assignments.cancel',
        'maintenance.assignments.create',
        'maintenance.attachments.create',
        'maintenance.categories.create',
        'maintenance.categories.delete',
        'maintenance.categories.update',
        'maintenance.costs.approve',
        'maintenance.costs.create',
        'maintenance.costs.reject',
        'maintenance.interventions.cancel',
        'maintenance.interventions.complete',
        'maintenance.interventions.create',
        'maintenance.interventions.start',
        'maintenance.suppliers.create',
        'maintenance.suppliers.update',
        'maintenance_requests.cancel',
        'maintenance_requests.close',
        'maintenance_requests.resolve',
        'maintenance_requests.review',
        'maintenance_requests.schedule',
        'maintenance_requests.start',
        'visits.availabilities.create',
        'visits.availabilities.delete',
        'visits.availabilities.generate_slots',
        'visits.availabilities.update',
        'visits.cancel',
        'visits.complete',
        'visits.confirm',
        'visits.mark_no_show',
        'visits.slots.block',
        'visits.slots.unblock',
    ];

    private const CRITICAL_DOWNLOADS = [
        'inspections.attachments.download',
        'inspections.reports.download',
        'maintenance.attachments.download',
    ];

    private const SENSITIVE_PERMISSIONS = [
        'inspections.attachments.create',
        'inspections.attachments.download',
        'inspections.cancel',
        'inspections.close',
        'inspections.complete',
        'inspections.items.create',
        'inspections.items.update',
        'inspections.reports.cancel',
        'inspections.reports.download',
        'inspections.reports.generate',
        'inspections.reports.validate',
        'inspections.start',
        'inspections.templates.create',
        'inspections.templates.update',
        'inspections.validate',
        'maintenance.assignments.cancel',
        'maintenance.assignments.create',
        'maintenance.attachments.create',
        'maintenance.attachments.download',
        'maintenance.categories.create',
        'maintenance.categories.delete',
        'maintenance.categories.update',
        'maintenance.costs.approve',
        'maintenance.costs.create',
        'maintenance.costs.reject',
        'maintenance.interventions.cancel',
        'maintenance.interventions.complete',
        'maintenance.interventions.create',
        'maintenance.interventions.start',
        'maintenance.suppliers.create',
        'maintenance.suppliers.update',
        'maintenance_requests.cancel',
        'maintenance_requests.close',
        'maintenance_requests.resolve',
        'maintenance_requests.review',
        'maintenance_requests.schedule',
        'maintenance_requests.start',
        'visits.availabilities.create',
        'visits.availabilities.delete',
        'visits.availabilities.generate_slots',
        'visits.availabilities.update',
        'visits.cancel',
        'visits.complete',
        'visits.confirm',
        'visits.mark_no_show',
        'visits.slots.block',
        'visits.slots.unblock',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_catalog_seeds_all_new_permissions_with_hierarchical_actions(): void
    {
        $records = Permission::query()
            ->whereIn(
                'name',
                array_keys(self::NEW_PERMISSION_DEFINITIONS),
            )
            ->get()
            ->keyBy('name');

        $this->assertCount(56, $records);

        foreach (self::NEW_PERMISSION_DEFINITIONS as $name => [$module, $action]) {
            $permission = $records->get($name);

            $this->assertInstanceOf(
                Permission::class,
                $permission,
                $name,
            );
            $this->assertSame(
                $module,
                $permission->module,
                $name,
            );
            $this->assertSame(
                $action,
                $permission->action,
                $name,
            );
        }
    }

    public function test_system_roles_receive_exactly_the_approved_new_permissions(): void
    {
        $newPermissions = array_keys(
            self::NEW_PERMISSION_DEFINITIONS
        );

        foreach (self::EXPECTED_ROLE_PERMISSIONS as $roleName => $expected) {
            $actual = Role::query()
                ->where('name', $roleName)
                ->firstOrFail()
                ->permissions()
                ->whereIn('name', $newPermissions)
                ->pluck('name')
                ->sort()
                ->values()
                ->all();

            sort($expected);

            $this->assertSame(
                $expected,
                $actual,
                $roleName,
            );
        }
    }

    public function test_administrator_keeps_only_the_global_wildcard_and_has_effective_access(): void
    {
        $role = Role::query()
            ->where('name', 'administrator')
            ->firstOrFail();

        $this->assertSame(
            ['*'],
            $role->permissions()
                ->pluck('name')
                ->sort()
                ->values()
                ->all(),
        );

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole($role);

        foreach (array_keys(self::NEW_PERMISSION_DEFINITIONS) as $permission) {
            $this->assertTrue(
                $user->hasPermission($permission),
                $permission,
            );
        }
    }

    public function test_candidate_and_auditor_preserve_least_privilege(): void
    {
        $candidate = self::EXPECTED_ROLE_PERMISSIONS[
            'candidate'
        ];
        $auditor = self::EXPECTED_ROLE_PERMISSIONS[
            'auditor'
        ];

        $this->assertSame([], $candidate);

        $this->assertSame(
            [],
            array_values(
                array_intersect(
                    $auditor,
                    self::MUTATION_PERMISSIONS,
                )
            ),
        );

        $this->assertSame(
            [],
            array_values(
                array_intersect(
                    $auditor,
                    self::CRITICAL_DOWNLOADS,
                )
            ),
        );
    }

    public function test_catalog_exposes_labels_domains_and_sensitivity_without_fallbacks(): void
    {
        $catalog = app(
            PermissionCatalogService::class
        );

        foreach (self::NEW_PERMISSION_DEFINITIONS as $name => [$module, $action]) {
            $metadata = $catalog->metadata(
                $name,
                $module,
                $action,
            );

            $this->assertNotSame(
                'other',
                $metadata['domain'],
                $name,
            );
            $this->assertStringNotContainsString(
                'Ação não catalogada',
                $metadata['action_label'],
                $name,
            );
        }

        foreach (self::SENSITIVE_PERMISSIONS as $permission) {
            [$module, $action] =
                self::NEW_PERMISSION_DEFINITIONS[
                    $permission
                ];

            $this->assertTrue(
                $catalog->metadata(
                    $permission,
                    $module,
                    $action,
                )['sensitive'],
                $permission,
            );
        }
    }
}
