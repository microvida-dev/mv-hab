<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformMunicipalContextPermissionReuseTest extends TestCase
{
    public function test_context_routes_reuse_existing_exact_permission_without_catalog_expansion(): void
    {
        /** @var array<string, mixed> $definitions */
        $definitions = (array) config('mvhab.permissions', []);
        $municipalityPermissions = $definitions['municipalities'] ?? null;

        $this->assertIsArray($municipalityPermissions);
        $this->assertContains('view', $municipalityPermissions);
        $this->assertArrayNotHasKey('platform_context', $definitions);

        foreach ([
            'backoffice.platform.municipal-context.index',
            'backoffice.platform.municipal-context.store',
            'backoffice.platform.municipal-context.destroy',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $this->assertContains(
                'permission:municipalities.view',
                $route->gatherMiddleware(),
                $routeName,
            );
        }
    }
}
