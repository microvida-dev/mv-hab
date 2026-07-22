<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuditAccessRoutesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_generates_json_inventory_with_current_role_middleware_findings(): void
    {
        $output = storage_path('framework/testing/access-route-audit.json');

        File::delete($output);

        $this->artisan('access:audit-routes', [
            '--format' => 'json',
            '--output' => $output,
        ])->assertSuccessful();

        $this->assertFileExists($output);

        $payload = json_decode(
            File::get($output),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('routes', $payload);
        $this->assertGreaterThan(0, $payload['summary']['total_routes']);

        $this->assertSame(
            1096,
            $payload['summary']['fixed_role_routes'],
        );

        $this->assertSame(
            875,
            $payload['summary']['backoffice_fixed_role_routes'],
        );

        $this->assertSame(
            220,
            $payload['summary']['candidate_fixed_role_routes'],
        );

        $this->assertSame(
            7,
            $payload['summary']['permission_middleware_routes'],
        );

        $this->assertSame(
            753,
            $payload['summary']['backoffice_fixed_role_without_active_backoffice'],
        );

        $this->assertSame(
            753,
            $payload['summary']['backoffice_fixed_role_without_mfa_backoffice'],
        );

        $this->assertSame(
            753,
            $payload['summary']['backoffice_fixed_role_without_log_backoffice'],
        );

        $applicationIntake = collect($payload['routes'])
            ->firstWhere('name', 'backoffice.application-intake.index');

        $this->assertNotNull($applicationIntake);

        $this->assertFalse(
            $applicationIntake['uses_fixed_role_middleware'],
        );

        $this->assertFalse(
            $applicationIntake['is_backoffice_role_route'],
        );

        $this->assertSame(
            [],
            $applicationIntake['roles'],
        );

        $this->assertContains(
            'permission:administrative_processes.create',
            $applicationIntake['permission_middleware'],
        );

        $this->assertSame(
            [
                'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
            ],
            $applicationIntake['excluded_middleware'],
        );

        $this->assertContains(
            'active.backoffice',
            $applicationIntake['middleware'],
        );

        $this->assertContains(
            'mfa.backoffice',
            $applicationIntake['middleware'],
        );

        $this->assertContains(
            'log.backoffice',
            $applicationIntake['middleware'],
        );

        $this->assertSame(
            [],
            $applicationIntake['missing_backoffice_guards'],
        );

        $applicationReviewRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.application-reviews.create',
                'backoffice.application-reviews.store',
                'backoffice.application-reviews.show',
                'backoffice.application-reviews.complete',
            ])
            ->keyBy('name');

        $this->assertCount(4, $applicationReviewRoutes);

        $expectedPermissions = [
            'backoffice.application-reviews.create'
                => 'permission:administrative_processes.create',
            'backoffice.application-reviews.store'
                => 'permission:administrative_processes.create',
            'backoffice.application-reviews.show'
                => 'permission:administrative_processes.view',
            'backoffice.application-reviews.complete'
                => 'permission:administrative_processes.update',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = $applicationReviewRoutes->get($routeName);

            $this->assertNotNull($route);
            $this->assertFalse($route['uses_fixed_role_middleware']);
            $this->assertFalse($route['is_backoffice_role_route']);
            $this->assertSame([], $route['roles']);

            $this->assertContains(
                $permissionMiddleware,
                $route['permission_middleware'],
            );

            $this->assertSame(
                [
                    'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
                ],
                $route['excluded_middleware'],
            );

            $this->assertContains('active.backoffice', $route['middleware']);
            $this->assertContains('mfa.backoffice', $route['middleware']);
            $this->assertContains('log.backoffice', $route['middleware']);
            $this->assertSame([], $route['missing_backoffice_guards']);
        }

        $candidateRoute = collect($payload['routes'])
            ->firstWhere('name', 'candidate.applications.create');

        $this->assertNotNull($candidateRoute);
        $this->assertTrue($candidateRoute['uses_fixed_role_middleware']);
        $this->assertFalse($candidateRoute['is_backoffice_role_route']);
        $this->assertSame([], $candidateRoute['missing_backoffice_guards']);

        File::delete($output);
    }

    public function test_command_can_limit_output_to_routes_with_fixed_role_middleware(): void
    {
        $output = storage_path('framework/testing/fixed-role-route-audit.json');

        File::delete($output);

        $this->artisan('access:audit-routes', [
            '--format' => 'json',
            '--output' => $output,
            '--only-fixed-role' => true,
        ])->assertSuccessful();

        $payload = json_decode(
            File::get($output),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotEmpty($payload['routes']);

        $this->assertTrue(
            collect($payload['routes'])
                ->every(
                    fn (array $route): bool => $route['uses_fixed_role_middleware'] === true
                ),
        );

        File::delete($output);
    }
}
