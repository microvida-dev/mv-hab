<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdministrationAccessPermissionRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_administration_routes_are_permission_first_and_keep_backoffice_guards(): void
    {
        $expected = [
            'backoffice.users.index' => 'permission:users.view',
            'backoffice.users.create' => 'permission:users.create',
            'backoffice.users.store' => 'permission:users.create',
            'backoffice.users.show' => 'permission:users.view',
            'backoffice.users.edit' => 'permission:users.update',
            'backoffice.users.update' => 'permission:users.update',
            'backoffice.users.deactivate' => 'permission:users.deactivate',
            'backoffice.users.reactivate' => 'permission:users.reactivate',
            'backoffice.users.force-mfa' => 'permission:users.force_mfa',
            'backoffice.users.reset-password' => 'permission:users.reset_password',
            'backoffice.teams.index' => 'permission:teams.view',
            'backoffice.teams.create' => 'permission:teams.create',
            'backoffice.teams.store' => 'permission:teams.create',
            'backoffice.teams.show' => 'permission:teams.view',
            'backoffice.teams.edit' => 'permission:teams.update',
            'backoffice.teams.update' => 'permission:teams.update',
            'backoffice.teams.members.store' => 'permission:teams.manage_members',
            'backoffice.teams.members.remove' => 'permission:teams.manage_members',
            'backoffice.access-audit.index' => 'permission:access_audit.view',
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
