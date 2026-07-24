<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use JsonException;
use ReflectionMethod;
use Tests\TestCase;

class HearingsComplaintsListsPermissionRoutesTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_sprint_47d_manifest_routes_are_permission_first(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(
                base_path('docs/access/manifests/sprint-47d-route-manifest.json'),
            ),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $definitions = $manifest['routes'] ?? null;

        $this->assertIsArray($definitions);
        $this->assertCount(78, $definitions);

        $routeNames = [];

        foreach ($definitions as $definition) {
            $this->assertIsArray($definition);

            $routeName = $definition['route_name'] ?? null;
            $permission = $definition['permission_final'] ?? null;
            $feature = $definition['feature_final'] ?? null;
            $controller = $definition['controller'] ?? null;
            $action = $definition['action'] ?? null;
            $policy = $definition['policy'] ?? null;
            $ability = $definition['ability'] ?? null;
            $principalModel = $definition['principal_model'] ?? null;

            $this->assertIsString($routeName);
            $this->assertIsString($permission);
            $this->assertSame('applications.review', $feature, $routeName);
            $this->assertIsString($controller);
            $this->assertIsString($action);
            $this->assertIsString($policy);
            $this->assertIsString($ability);
            $this->assertIsString($principalModel);
            $this->assertFalse(str_starts_with($routeName, 'candidate.'), $routeName);

            $routeNames[] = $routeName;
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $this->assertSame($definition['uri'], $route->uri(), $routeName);
            $this->assertSame(
                $this->normalizedMethods($definition['http_methods']),
                $this->normalizedMethods($route->methods()),
                $routeName,
            );
            $this->assertSame("{$controller}@{$action}", $route->getActionName(), $routeName);

            $fixedRole = $definition['fixed_role_before'] ?? null;
            if (is_string($fixedRole)) {
                $this->assertContains($fixedRole, $route->excludedMiddleware(), $routeName);
            }

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
                ['municipality.feature:applications.review'],
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

            $this->assertTrue(class_exists($policy), $policy);
            $this->assertTrue(method_exists($policy, $ability), "{$policy}::{$ability}");
            $this->assertTrue(class_exists($principalModel), $principalModel);
            $this->assertInstanceOf($policy, Gate::getPolicyFor($principalModel), $routeName);

            $formRequest = $definition['form_request'] ?? null;
            if (is_string($formRequest)) {
                $this->assertFormRequestDoesNotAuthorizeUnconditionally(
                    $formRequest,
                    $routeName,
                );
            }
        }

        $this->assertCount(78, array_unique($routeNames));
    }

    /**
     * @return list<string>
     */
    private function normalizedMethods(mixed $methods): array
    {
        $this->assertIsArray($methods);

        $normalized = array_values(array_filter(
            $methods,
            fn (mixed $method): bool => is_string($method) && $method !== 'HEAD',
        ));
        sort($normalized);

        return $normalized;
    }

    private function assertFormRequestDoesNotAuthorizeUnconditionally(
        string $formRequest,
        string $routeName,
    ): void {
        $this->assertTrue(class_exists($formRequest), $formRequest);
        $this->assertTrue(is_subclass_of($formRequest, FormRequest::class), $formRequest);
        $this->assertTrue(method_exists($formRequest, 'authorize'), $formRequest);

        $method = new ReflectionMethod($formRequest, 'authorize');
        $fileName = $method->getFileName();

        if ($fileName === false) {
            self::fail("Não foi possível localizar o Form Request de {$routeName}.");
        }

        $sourceLines = file($fileName, FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($sourceLines);

        $source = implode(
            "\n",
            array_slice(
                $sourceLines,
                $method->getStartLine() - 1,
                $method->getEndLine() - $method->getStartLine() + 1,
            ),
        );

        $this->assertDoesNotMatchRegularExpression(
            '/function\s+authorize\s*\([^)]*\)\s*:\s*bool\s*\{\s*return\s+true\s*;\s*\}/s',
            $source,
            $routeName,
        );
    }
}
