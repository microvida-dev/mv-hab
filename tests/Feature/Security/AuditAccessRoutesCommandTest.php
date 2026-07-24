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
            439,
            $payload['summary']['fixed_role_routes'],
        );

        $this->assertSame(
            219,
            $payload['summary']['backoffice_fixed_role_routes'],
        );

        $this->assertSame(
            220,
            $payload['summary']['candidate_fixed_role_routes'],
        );

        $this->assertSame(
            687,
            $payload['summary']['permission_middleware_routes'],
        );

        $this->assertSame(
            178,
            $payload['summary']['backoffice_fixed_role_without_active_backoffice'],
        );

        $this->assertSame(
            178,
            $payload['summary']['backoffice_fixed_role_without_mfa_backoffice'],
        );

        $this->assertSame(
            178,
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

        $this->assertContains(
            'municipality.feature:applications.intake',
            $applicationIntake['middleware'],
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

        $administrativeProcessIndex = collect($payload['routes'])
            ->firstWhere('name', 'backoffice.administrative-processes.index');

        $this->assertNotNull($administrativeProcessIndex);

        $this->assertFalse(
            $administrativeProcessIndex['uses_fixed_role_middleware'],
        );

        $this->assertFalse(
            $administrativeProcessIndex['is_backoffice_role_route'],
        );

        $this->assertSame(
            [],
            $administrativeProcessIndex['roles'],
        );

        $this->assertContains(
            'permission:administrative_processes.view',
            $administrativeProcessIndex['permission_middleware'],
        );

        $this->assertSame(
            [
                'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor',
            ],
            $administrativeProcessIndex['excluded_middleware'],
        );

        $this->assertContains(
            'active.backoffice',
            $administrativeProcessIndex['middleware'],
        );

        $this->assertContains(
            'mfa.backoffice',
            $administrativeProcessIndex['middleware'],
        );

        $this->assertContains(
            'log.backoffice',
            $administrativeProcessIndex['middleware'],
        );

        $this->assertSame(
            [],
            $administrativeProcessIndex['missing_backoffice_guards'],
        );

        $expectedPermissions = [
            'backoffice.application-reviews.create' => 'permission:administrative_processes.create',
            'backoffice.application-reviews.store' => 'permission:administrative_processes.create',
            'backoffice.application-reviews.show' => 'permission:administrative_processes.view',
            'backoffice.application-reviews.complete' => 'permission:administrative_processes.update',
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
            $this->assertContains('municipality.feature:applications.review', $route['middleware']);
            $this->assertSame([], $route['missing_backoffice_guards']);
        }

        $administrativeProcessBackofficeRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.administrative-processes.show',
                'backoffice.administrative-processes.timeline',
            ])
            ->keyBy('name');

        $this->assertCount(2, $administrativeProcessBackofficeRoutes);

        $expectedPermissions = [
            'backoffice.administrative-processes.show' => 'permission:administrative_processes.view',

            'backoffice.administrative-processes.timeline' => 'permission:administrative_processes.audit,administrative_processes.view',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = $administrativeProcessBackofficeRoutes->get($routeName);

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

            $this->assertSame(
                [],
                $route['missing_backoffice_guards'],
            );
        }

        $applicationBackofficeRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.applications.index',
                'backoffice.applications.show',
                'backoffice.applications.timeline',
            ])
            ->keyBy('name');

        $this->assertCount(3, $applicationBackofficeRoutes);

        $applicationBackofficePermissions = [
            'backoffice.applications.index' => 'permission:applications.view',

            'backoffice.applications.show' => 'permission:applications.view',

            'backoffice.applications.timeline' => 'permission:applications.audit,applications.view',
        ];

        foreach ($applicationBackofficePermissions as $routeName => $permissionMiddleware) {
            $route = $applicationBackofficeRoutes->get($routeName);

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
            $this->assertContains('municipality.feature:applications.review', $route['middleware']);
            $this->assertSame([], $route['missing_backoffice_guards']);
        }

        $artifactRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.applications.report.show',
                'backoffice.applications.report.generate',
                'backoffice.application-reports.download',
                'backoffice.applications.document-dossier.show',
                'backoffice.applications.document-dossier.generate',
                'backoffice.document-dossiers.download',
            ])
            ->keyBy('name');

        $this->assertCount(6, $artifactRoutes);

        $artifactPermissions = [
            'backoffice.applications.report.show' => 'permission:reports.view',

            'backoffice.applications.report.generate' => 'permission:reports.create,reports.export',

            'backoffice.application-reports.download' => 'permission:reports.view,reports.export',

            'backoffice.applications.document-dossier.show' => 'permission:documents.view',

            'backoffice.applications.document-dossier.generate' => 'permission:documents.create,documents.export',

            'backoffice.document-dossiers.download' => 'permission:documents.view',
        ];

        foreach ($artifactPermissions as $routeName => $permissionMiddleware) {
            $route = $artifactRoutes->get($routeName);

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

            if (str_contains($routeName, 'report')) {
                $this->assertContains('municipality.feature:applications.export', $route['middleware']);
                $this->assertContains('permission:applications.export', $route['permission_middleware']);
            }

            $this->assertSame([], $route['missing_backoffice_guards']);
        }

        $processTrackingRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.applications.public-status.show',
                'backoffice.applications.public-status.update',
                'backoffice.applications.process-confirmations.generate',
                'backoffice.process-confirmations.index',
                'backoffice.process-confirmations.show',
                'backoffice.process-confirmations.send',
            ])
            ->keyBy('name');

        $this->assertCount(6, $processTrackingRoutes);

        $processTrackingPermissions = [
            'backoffice.applications.public-status.show' => 'permission:applications.view',

            'backoffice.applications.public-status.update' => 'permission:applications.update',

            'backoffice.applications.process-confirmations.generate' => 'permission:applications.update,applications.approve',

            'backoffice.process-confirmations.index' => 'permission:applications.view',

            'backoffice.process-confirmations.show' => 'permission:applications.view',

            'backoffice.process-confirmations.send' => 'permission:applications.update,applications.approve',
        ];

        foreach ($processTrackingPermissions as $routeName => $permissionMiddleware) {
            $route = $processTrackingRoutes->get($routeName);

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

        $additionalDocumentRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'backoffice.additional-document-requests.index',
                'backoffice.additional-document-requests.store',
                'backoffice.additional-document-submissions.index',
                'backoffice.additional-document-submissions.show',
                'backoffice.additional-document-submissions.decide',
            ])
            ->keyBy('name');

        $this->assertCount(5, $additionalDocumentRoutes);

        $additionalDocumentPermissions = [
            'backoffice.additional-document-requests.index' => 'permission:documents.view,applications.view',

            'backoffice.additional-document-requests.store' => 'permission:documents.create,applications.update',

            'backoffice.additional-document-submissions.index' => 'permission:documents.view,applications.view',

            'backoffice.additional-document-submissions.show' => 'permission:documents.view,applications.view',

            'backoffice.additional-document-submissions.decide' => 'permission:documents.approve,documents.reject',
        ];

        foreach ($additionalDocumentPermissions as $routeName => $permissionMiddleware) {
            $route = $additionalDocumentRoutes->get($routeName);

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

        $documentReviewRoutes = collect($payload['routes'])
            ->whereIn('name', [
                'admin.document-reviews.index',
                'admin.document-reviews.show',
                'admin.document-reviews.preview',
                'admin.document-reviews.download',
                'admin.document-reviews.under-review',
                'admin.document-reviews.validate',
                'admin.document-reviews.reject',
                'admin.document-reviews.document-ai',
            ])
            ->keyBy('name');

        $this->assertCount(8, $documentReviewRoutes);

        $documentReviewPermissions = [
            'admin.document-reviews.index' => 'permission:documents.view',

            'admin.document-reviews.show' => 'permission:documents.view',

            'admin.document-reviews.preview' => 'permission:documents.view',

            'admin.document-reviews.download' => 'permission:documents.view',

            'admin.document-reviews.under-review' => 'permission:documents.approve',

            'admin.document-reviews.validate' => 'permission:documents.approve',

            'admin.document-reviews.reject' => 'permission:documents.reject',

            'admin.document-reviews.document-ai' => 'permission:documents.approve',
        ];

        foreach ($documentReviewPermissions as $routeName => $permissionMiddleware) {
            $route = $documentReviewRoutes->get($routeName);

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
            $this->assertContains('municipality.feature:applications.review', $route['middleware']);
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
