<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PrivacyPermissionRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_routes_are_permission_first_and_keep_backoffice_guards(): void
    {
        $expected = [
            'backoffice.security.privacy.anonymization.approve' => 'permission:rgpd.anonymization.approve',
            'backoffice.security.privacy.anonymization.index' => 'permission:rgpd.anonymization.view',
            'backoffice.security.privacy.anonymization.run' => 'permission:rgpd.anonymization.execute',
            'backoffice.security.privacy.anonymization.show' => 'permission:rgpd.anonymization.view',
            'backoffice.security.privacy.anonymization.store' => 'permission:rgpd.anonymization.request',
            'backoffice.security.privacy.exports.download' => 'permission:privacy.export',
            'backoffice.security.privacy.exports.show' => 'permission:privacy.export',
            'backoffice.security.privacy.purposes.index' => 'permission:privacy.view',
            'backoffice.security.privacy.purposes.store' => 'permission:privacy.create',
            'backoffice.security.privacy.purposes.update' => 'permission:privacy.update',
            'backoffice.security.privacy.requests.assign' => 'permission:privacy.assign',
            'backoffice.security.privacy.requests.complete' => 'permission:privacy.approve',
            'backoffice.security.privacy.requests.exports.store' => 'permission:privacy.export',
            'backoffice.security.privacy.requests.index' => 'permission:privacy.view',
            'backoffice.security.privacy.requests.reject' => 'permission:privacy.reject',
            'backoffice.security.privacy.requests.show' => 'permission:privacy.view',
            'backoffice.security.privacy.requests.store' => 'permission:privacy.create',
            'backoffice.security.privacy.retention-executions.approve' => 'permission:rgpd.retention.approve',
            'backoffice.security.privacy.retention-executions.run' => 'permission:rgpd.retention.execute',
            'backoffice.security.privacy.retention.index' => 'permission:rgpd.retention.view',
            'backoffice.security.privacy.retention.simulate' => 'permission:rgpd.retention.manage',
            'backoffice.security.privacy.retention.store' => 'permission:rgpd.retention.manage',
            'backoffice.security.privacy.retention.update' => 'permission:rgpd.retention.manage',
            'backoffice.cases.rgpd.show' => 'permission:privacy.view',
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware, $routeName);
            $this->assertContains('active.backoffice', $middleware, $routeName);
            $this->assertContains('mfa.backoffice', $middleware, $routeName);
            $this->assertContains('log.backoffice', $middleware, $routeName);
            $this->assertContains($permission, $middleware, $routeName);
            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                $routeName,
            );
        }
    }
}
