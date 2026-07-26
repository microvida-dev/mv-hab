<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class MaintenanceInspectionsVisitsPermissionRoutesTest extends TestCase
{
    private const EXPECTED_ROUTE_COUNT = 96;

    private const EXPECTED_DOMAIN_COUNTS = [
        'agenda' => 1,
        'inspections' => 26,
        'maintenance' => 51,
        'visits' => 18,
    ];

    private const REQUIRED_BACKOFFICE_MIDDLEWARE = [
        'active.backoffice',
        'mfa.backoffice',
        'log.backoffice',
    ];

    public function test_manifest_scope_is_complete_unique_and_reconciled(): void
    {
        $routes = $this->manifestRoutes();

        $this->assertCount(
            self::EXPECTED_ROUTE_COUNT,
            $routes,
            'O manifesto da Sprint 47G deve conter exatamente 96 rotas.',
        );

        $routeNames = [];
        $domainCounts = [];

        foreach ($routes as $index => $route) {
            $context = sprintf('entrada %d do manifesto', $index);

            $routeName = $this->requiredString(
                $route,
                'resolved_route_name',
                $context,
            );

            $domain = $this->requiredString(
                $route,
                'bounded_context',
                $routeName,
            );

            $permission = $this->requiredString(
                $route,
                'resolved_permission',
                $routeName,
            );

            $controller = $this->requiredString(
                $route,
                'resolved_controller',
                $routeName,
            );

            $action = $this->requiredString(
                $route,
                'resolved_action',
                $routeName,
            );

            $uri = $this->requiredString(
                $route,
                'resolved_uri',
                $routeName,
            );

            $methods = $this->requiredStringList(
                $route,
                'resolved_http_methods',
                $routeName,
            );

            $this->assertNotSame(
                [],
                $methods,
                "{$routeName}: deve declarar pelo menos um método HTTP.",
            );

            $this->assertTrue(
                ($route['mfa_required'] ?? null) === true,
                "{$routeName}: todas as rotas da Sprint 47G devem exigir MFA.",
            );

            $this->assertNotSame(
                '',
                $permission,
                "{$routeName}: permission resolvida não pode estar vazia.",
            );

            $this->assertNotSame(
                '',
                $controller,
                "{$routeName}: controller resolvido não pode estar vazio.",
            );

            $this->assertNotSame(
                '',
                $action,
                "{$routeName}: action resolvida não pode estar vazia.",
            );

            $this->assertNotSame(
                '',
                $uri,
                "{$routeName}: URI resolvido não pode estar vazio.",
            );

            $routeNames[] = $routeName;
            $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
        }

        $this->assertCount(
            self::EXPECTED_ROUTE_COUNT,
            array_unique($routeNames),
            'O manifesto não pode conter nomes de rota duplicados.',
        );

        ksort($domainCounts);

        $this->assertSame(
            self::EXPECTED_DOMAIN_COUNTS,
            $domainCounts,
            'A distribuição dos bounded contexts da Sprint 47G foi alterada.',
        );
    }

    public function test_runtime_routes_match_permission_first_manifest(): void
    {
        $manifestRoutes = $this->manifestRoutes();

        $routeCollection = Route::getRoutes();
        $routeCollection->refreshNameLookups();

        /** @var array<string, list<LaravelRoute>> $runtimeRoutesByName */
        $runtimeRoutesByName = [];

        foreach ($routeCollection->getRoutes() as $runtimeRoute) {
            $routeName = $runtimeRoute->getName();

            if (! is_string($routeName) || $routeName === '') {
                continue;
            }

            $runtimeRoutesByName[$routeName][] = $runtimeRoute;
        }

        $validatedRoutes = 0;

        foreach ($manifestRoutes as $manifestRoute) {
            $routeName = $this->requiredString(
                $manifestRoute,
                'resolved_route_name',
                'manifesto',
            );

            $matches = $runtimeRoutesByName[$routeName] ?? [];

            if (count($matches) !== 1) {
                self::fail(sprintf(
                    '%s: esperada exatamente uma rota runtime; encontradas %d.',
                    $routeName,
                    count($matches),
                ));
            }

            $runtimeRoute = $matches[0];

            $expectedMethods = $this->normalizeMethods(
                $this->requiredStringList(
                    $manifestRoute,
                    'resolved_http_methods',
                    $routeName,
                ),
            );

            $actualMethods = $this->normalizeMethods(
                array_values($runtimeRoute->methods()),
            );

            $expectedUri = $this->requiredString(
                $manifestRoute,
                'resolved_uri',
                $routeName,
            );

            $expectedController = $this->requiredString(
                $manifestRoute,
                'resolved_controller',
                $routeName,
            );

            $expectedAction = $this->requiredString(
                $manifestRoute,
                'resolved_action',
                $routeName,
            );

            $expectedPermission = $this->requiredString(
                $manifestRoute,
                'resolved_permission',
                $routeName,
            );

            $this->assertSame(
                $expectedMethods,
                $actualMethods,
                "{$routeName}: métodos HTTP divergiram do manifesto.",
            );

            $this->assertSame(
                $expectedUri,
                $runtimeRoute->uri(),
                "{$routeName}: URI divergiu do manifesto.",
            );

            $expectedActionName = $expectedAction === '__invoke'
                ? $expectedController
                : "{$expectedController}@{$expectedAction}";

            $this->assertSame(
                $expectedActionName,
                $runtimeRoute->getActionName(),
                "{$routeName}: controller/action divergiram do manifesto.",
            );

            /*
             * gatherMiddleware() inclui middleware herdado antes das
             * exclusões configuradas por withoutMiddleware().
             *
             * Para validar o conjunto nominal efetivo, removemos as
             * exclusões exatamente como declarado na rota.
             */
            $middleware = array_values(array_diff(
                $runtimeRoute->gatherMiddleware(),
                $runtimeRoute->excludedMiddleware(),
            ));

            $permissionMiddleware = array_values(array_filter(
                $middleware,
                static fn (string $item): bool => str_starts_with(
                    $item,
                    'permission:',
                ),
            ));

            $roleMiddleware = array_values(array_filter(
                $middleware,
                static fn (string $item): bool => str_starts_with(
                    $item,
                    'role:',
                ),
            ));

            $this->assertSame(
                ["permission:{$expectedPermission}"],
                $permissionMiddleware,
                "{$routeName}: deve possuir uma única permission exata.",
            );

            $this->assertSame(
                [],
                $roleMiddleware,
                "{$routeName}: não pode conservar middleware role:*.",
            );

            foreach (self::REQUIRED_BACKOFFICE_MIDDLEWARE as $requiredMiddleware) {
                $this->assertContains(
                    $requiredMiddleware,
                    $middleware,
                    "{$routeName}: middleware {$requiredMiddleware} ausente.",
                );
            }

            $validatedRoutes++;
        }

        $this->assertSame(
            self::EXPECTED_ROUTE_COUNT,
            $validatedRoutes,
            'O teste não validou todas as 96 rotas da Sprint 47G.',
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function manifestRoutes(): array
    {
        $path = base_path(
            'docs/access/manifests/sprint-47g-route-manifest.json',
        );

        $json = file_get_contents($path);

        if ($json === false) {
            self::fail(
                "Não foi possível ler o manifesto da Sprint 47G: {$path}",
            );
        }

        $decoded = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! is_array($decoded)) {
            self::fail(
                'O manifesto da Sprint 47G não contém um objeto JSON válido.',
            );
        }

        $rawRoutes = $decoded['routes'] ?? null;

        if (! is_array($rawRoutes)) {
            self::fail(
                'O manifesto da Sprint 47G não contém a coleção routes.',
            );
        }

        $routes = [];

        foreach ($rawRoutes as $index => $route) {
            if (! is_array($route)) {
                self::fail(
                    sprintf(
                        'A entrada %s do manifesto não é um objeto.',
                        (string) $index,
                    ),
                );
            }

            $routes[] = $route;
        }

        return $routes;
    }

    /**
     * @param  array<string, mixed>  $route
     */
    private function requiredString(
        array $route,
        string $field,
        string $context,
    ): string {
        $value = $route[$field] ?? null;

        if (! is_string($value) || $value === '') {
            self::fail(
                "{$context}: campo {$field} ausente ou inválido.",
            );
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $route
     * @return list<string>
     */
    private function requiredStringList(
        array $route,
        string $field,
        string $context,
    ): array {
        $value = $route[$field] ?? null;

        if (! is_array($value)) {
            self::fail(
                "{$context}: campo {$field} não é uma lista.",
            );
        }

        $values = [];

        foreach ($value as $item) {
            if (! is_string($item) || $item === '') {
                self::fail(
                    "{$context}: campo {$field} contém um valor inválido.",
                );
            }

            $values[] = $item;
        }

        return $values;
    }

    /**
     * O runtime Laravel adiciona HEAD automaticamente às rotas GET.
     *
     * @param  list<string>  $methods
     * @return list<string>
     */
    private function normalizeMethods(array $methods): array
    {
        $normalized = [];

        foreach ($methods as $method) {
            $method = strtoupper($method);

            if ($method === 'HEAD') {
                continue;
            }

            $normalized[] = $method;
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }
}
