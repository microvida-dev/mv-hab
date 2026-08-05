<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Platform\ActorProfileResolver;
use App\Services\Platform\PlatformOperatorScopeService;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class PlatformScopeInferenceTest extends TestCase
{
    public function test_dashboard_authorization_declares_canonical_profile_resolver(): void
    {
        $dependencies = $this->constructorDependencies(
            DashboardAuthorizationService::class,
        );

        $this->assertContains(
            ActorProfileResolver::class,
            $dependencies,
        );
    }

    public function test_actor_profile_resolver_declares_structural_platform_scope(): void
    {
        $dependencies = $this->constructorDependencies(
            ActorProfileResolver::class,
        );

        $this->assertContains(
            PlatformOperatorScopeService::class,
            $dependencies,
        );
    }

    /**
     * @param  class-string  $class
     * @return list<class-string>
     */
    private function constructorDependencies(string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        $this->assertNotNull($constructor);

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType
                || $type->isBuiltin()) {
                continue;
            }

            $name = $type->getName();

            if (class_exists($name) || interface_exists($name)) {
                $dependencies[] = $name;
            }
        }

        return array_values($dependencies);
    }
}
