<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Services\Access\AccessChangeLogger;
use App\Services\Access\AccessMunicipalScopeService;
use App\Services\Access\MunicipalTeamService;
use App\Services\Access\RoleManagementService;
use App\Services\Access\UserAdministrationService;
use App\Services\Platform\PlatformMunicipalContextService;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class PlatformMunicipalAccessArchitectureTest extends TestCase
{
    private const PREFIXES = ['backoffice.users.', 'backoffice.roles.', 'backoffice.teams.'];

    public function test_access_services_depend_on_the_canonical_context(): void
    {
        foreach ([AccessMunicipalScopeService::class, AccessChangeLogger::class] as $class) {
            $constructor = (new ReflectionClass($class))->getConstructor();
            $this->assertNotNull($constructor);
            $dependencies = collect($constructor->getParameters())
                ->map(fn ($parameter) => $parameter->getType())
                ->filter(fn ($type): bool => $type instanceof ReflectionNamedType && ! $type->isBuiltin())
                ->map(fn (ReflectionNamedType $type): string => $type->getName())
                ->all();
            $this->assertContains(PlatformMunicipalContextService::class, $dependencies, $class);
        }
    }

    public function test_access_mutation_services_depend_on_the_effective_municipal_scope(): void
    {
        foreach ([
            UserAdministrationService::class,
            MunicipalTeamService::class,
            RoleManagementService::class,
        ] as $class) {
            $constructor = (new ReflectionClass($class))->getConstructor();
            $this->assertNotNull($constructor);

            $dependencies = collect($constructor->getParameters())
                ->map(fn ($parameter) => $parameter->getType())
                ->filter(fn ($type): bool => $type instanceof ReflectionNamedType && ! $type->isBuiltin())
                ->map(fn (ReflectionNamedType $type): string => $type->getName())
                ->all();

            $this->assertContains(
                AccessMunicipalScopeService::class,
                $dependencies,
                $class,
            );
        }
    }

    public function test_only_the_approved_access_batch_requires_context_here(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => collect(self::PREFIXES)->contains(
                fn (string $prefix): bool => str_starts_with((string) $route->getName(), $prefix),
            ))->values();
        $this->assertCount(33, $routes);
        foreach ($routes as $route) {
            $middleware = app('router')->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());
            $this->assertContains('municipality.context', $middleware, (string) $route->getName());
        }
    }
}
