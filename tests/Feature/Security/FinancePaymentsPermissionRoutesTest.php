<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Route;
use JsonException;
use Tests\TestCase;

class FinancePaymentsPermissionRoutesTest extends TestCase
{
    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    /**
     * @throws JsonException
     */
    public function test_sprint_47f_manifest_routes_are_permission_first(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(
                base_path('docs/access/manifests/sprint-47f-route-manifest.json'),
            ),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $definitions = $manifest['routes'] ?? null;

        $this->assertIsArray($definitions);
        $this->assertCount(99, $definitions);

        $routeNames = [];

        foreach ($definitions as $definition) {
            $this->assertIsArray($definition);

            $routeName = $definition['resolved_route_name'] ?? null;
            $permission = $definition['resolved_permission'] ?? null;
            $feature = $definition['resolved_feature'] ?? null;

            $this->assertIsString($routeName);
            $this->assertIsString($permission);
            $this->assertNull($feature, $routeName);

            $routeNames[] = $routeName;
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
                $routeName,
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            foreach (['auth', 'active.backoffice', 'mfa.backoffice', 'log.backoffice'] as $guard) {
                $this->assertContains($guard, $middleware, "{$routeName}: {$guard}");
            }

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                $routeName,
            );
            $this->assertSame(
                ["permission:{$permission}"],
                collect($middleware)
                    ->filter(fn (string $item): bool => str_starts_with($item, 'permission:'))
                    ->values()
                    ->all(),
                $routeName,
            );
            $this->assertSame(
                [],
                collect($middleware)
                    ->filter(
                        fn (string $item): bool => str_starts_with(
                            $item,
                            'municipality.feature:',
                        ),
                    )
                    ->values()
                    ->all(),
                $routeName,
            );
        }

        $this->assertCount(99, array_unique($routeNames));
    }
}
