<?php

namespace Tests\Feature\Security;

use App\Enums\AllocationReportStatus;
use App\Models\AllocationReport;
use App\Models\AllocationRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Allocation\AllocationReportService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Tests\TestCase;

class AllocationReportsPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_allocation_report_routes_use_expected_permission_middleware(): void
    {
        $expectedPermissions = [
            'backoffice.allocation.reports.index' => 'permission:allocations.view',
            'backoffice.allocation.reports.store' => 'permission:allocations.create',
            'backoffice.allocation.reports.show' => 'permission:allocations.view',
            'backoffice.allocation.reports.approve' => 'permission:allocations.approve',
            'backoffice.allocation.reports.download' => 'permission:allocations.export',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
                "Route [{$routeName}] does not exclude inherited fixed-role middleware.",
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains($permissionMiddleware, $middleware);

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                "Route [{$routeName}] still contains active role middleware.",
            );
        }
    }

    public function test_view_permission_can_read_reports_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $report = AllocationReport::factory()->create();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.show', $report),
        )->assertOk();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.store'),
            ['allocation_run_id' => AllocationRun::factory()->create()->id],
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.download', $report),
        )->assertForbidden();
    }

    public function test_create_permission_can_generate_report_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $run = AllocationRun::factory()->create();
        $report = AllocationReport::factory()->create([
            'allocation_run_id' => $run->id,
        ]);

        $this->mock(
            AllocationReportService::class,
            function (MockInterface $mock) use ($run, $report): void {
                $mock->shouldReceive('generate')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationRun $receivedRun,
                            User $actor,
                        ): bool => $receivedRun->is($run)
                            && $actor->id !== null,
                    )
                    ->andReturn($report);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.store'),
            ['allocation_run_id' => $run->id],
        )->assertRedirect(
            route('backoffice.allocation.reports.show', $report),
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.approve', $report),
        )->assertForbidden();
    }

    public function test_approve_permission_can_approve_only(): void
    {
        $user = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $report = AllocationReport::factory()->create([
            'status' => AllocationReportStatus::Generated,
        ]);

        $this->mock(
            AllocationReportService::class,
            function (MockInterface $mock) use ($report): void {
                $mock->shouldReceive('approve')
                    ->once()
                    ->withArgs(
                        fn (
                            AllocationReport $receivedReport,
                            User $actor,
                        ): bool => $receivedReport->is($report)
                            && $actor->id !== null,
                    )
                    ->andReturn($report);
            },
        );

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.approve', $report),
        )->assertRedirect();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.download', $report),
        )->assertForbidden();
    }

    public function test_export_permission_can_download_without_exposing_internal_path(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('private/reports/report.html', '<p>Relatório</p>');

        $user = $this->userWithCustomRole([
            'allocations.export',
        ]);

        $report = AllocationReport::factory()->create([
            'file_disk' => 'local',
            'file_path' => 'private/reports/report.html',
        ]);

        $response = $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.download', $report),
        );

        $response->assertOk();

        $this->assertStringNotContainsString(
            'private/reports',
            (string) $response->headers->get('content-disposition'),
        );

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.index'),
        )->assertForbidden();
    }

    public function test_user_without_allocation_permissions_is_blocked_and_does_not_change_status(): void
    {
        $user = $this->userWithCustomRole([]);
        $report = AllocationReport::factory()->create([
            'status' => AllocationReportStatus::Generated,
        ]);

        $this->getAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.index'),
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $user,
            route('backoffice.allocation.reports.approve', $report),
        )->assertForbidden();

        $this->assertSame(
            AllocationReportStatus::Generated,
            $report->fresh()->status,
        );
    }

    public function test_candidate_is_blocked_even_with_all_allocation_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'allocations.view',
            'allocations.create',
            'allocations.approve',
            'allocations.export',
        ]);

        $report = AllocationReport::factory()->create();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.reports.index'),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.reports.show', $report),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $candidate,
            route('backoffice.allocation.reports.download', $report),
        )->assertForbidden();
    }

    public function test_auditor_can_read_but_cannot_create_or_approve_without_explicit_export(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'allocations.view',
            'allocations.create',
            'allocations.approve',
        ]);

        $report = AllocationReport::factory()->create();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reports.index'),
        )->assertOk();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reports.show', $report),
        )->assertOk();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reports.store'),
            ['allocation_run_id' => AllocationRun::factory()->create()->id],
        )->assertForbidden();

        $this->postAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reports.approve', $report),
        )->assertForbidden();

        $this->getAsBackofficeUser(
            $auditor,
            route('backoffice.allocation.reports.download', $report),
        )->assertForbidden();
    }

    public function test_backoffice_policy_abilities_match_permission_and_role_boundaries(): void
    {
        $viewer = $this->userWithCustomRole([
            'allocations.view',
        ]);

        $creator = $this->userWithCustomRole([
            'allocations.create',
        ]);

        $approver = $this->userWithCustomRole([
            'allocations.approve',
        ]);

        $exporter = $this->userWithCustomRole([
            'allocations.export',
        ]);

        $report = AllocationReport::factory()->create();

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewAnyBackoffice',
                AllocationReport::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($viewer)->allows(
                'viewBackoffice',
                $report,
            ),
        );

        $this->assertTrue(
            Gate::forUser($creator)->allows(
                'createBackoffice',
                AllocationReport::class,
            ),
        );

        $this->assertTrue(
            Gate::forUser($approver)->allows(
                'approveBackoffice',
                $report,
            ),
        );

        $this->assertTrue(
            Gate::forUser($exporter)->allows(
                'exportBackoffice',
                $report,
            ),
        );

        $this->assertFalse(
            Gate::forUser($viewer)->allows(
                'exportBackoffice',
                $report,
            ),
        );

        $this->assertFalse(
            Gate::forUser($approver)->allows(
                'createBackoffice',
                AllocationReport::class,
            ),
        );
    }

    private function getAsBackofficeUser(
        User $user,
        string $uri,
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($uri);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postAsBackofficeUser(
        User $user,
        string $uri,
        array $data = [],
    ): TestResponse {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post($uri, $data);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'allocation_reports_'.str()->random(8),
            'label' => 'Allocation reports permission test role',
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
