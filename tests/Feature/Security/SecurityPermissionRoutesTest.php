<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityPermissionRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_routes_are_permission_first_and_keep_backoffice_guards(): void
    {
        $expected = [
            'backoffice.security.dashboard' => 'permission:security.view',
            'backoffice.security.alert-rules.store' => 'permission:security.update',
            'backoffice.security.alert-rules.update' => 'permission:security.update',
            'backoffice.security.alerts.index' => 'permission:security.view',
            'backoffice.security.alerts.resolve' => 'permission:security.resolve',
            'backoffice.security.alerts.review' => 'permission:security.update',
            'backoffice.security.audit.access-logs.index' => 'permission:security.view_access_logs',
            'backoffice.security.audit.events.index' => 'permission:audit_logs.view',
            'backoffice.security.audit.events.show' => 'permission:audit_logs.view',
            'backoffice.security.audit.sensitive-logs.index' => 'permission:security.audit_sensitive_access',
            'backoffice.security.backups.index' => 'permission:security.view',
            'backoffice.security.backups.store' => 'permission:security.update',
            'backoffice.security.checklist-items.update' => 'permission:security.update',
            'backoffice.security.checklists.approve' => 'permission:security.approve',
            'backoffice.security.checklists.index' => 'permission:security.view',
            'backoffice.security.checklists.show' => 'permission:security.view',
            'backoffice.security.checklists.store' => 'permission:security.update',
            'backoffice.security.encrypted-fields.index' => 'permission:security.view',
            'backoffice.security.mfa.confirm' => 'permission:security.manage_own_mfa',
            'backoffice.security.mfa.disable' => 'permission:security.manage_own_mfa',
            'backoffice.security.mfa.enable' => 'permission:security.manage_own_mfa',
            'backoffice.security.mfa.index' => 'permission:security.manage_own_mfa',
            'backoffice.security.mfa.recovery-codes.regenerate' => 'permission:security.manage_own_mfa',
            'backoffice.security.mfa.verify' => 'permission:security.manage_own_mfa',
            'backoffice.security.permission-reviews.complete' => 'permission:permission_reviews.complete',
            'backoffice.security.permission-reviews.index' => 'permission:permission_reviews.view',
            'backoffice.security.permission-reviews.show' => 'permission:permission_reviews.view',
            'backoffice.security.permission-reviews.store' => 'permission:permission_reviews.create',
            'backoffice.security.storage.index' => 'permission:security.view',
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
