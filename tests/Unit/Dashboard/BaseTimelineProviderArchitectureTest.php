<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderRegistry;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class BaseTimelineProviderArchitectureTest extends TestCase
{
    #[Test]
    public function every_provider_extends_base_timeline_provider(): void
    {
        foreach ($this->providerClasses() as $class) {
            $reflection = new ReflectionClass($class);

            $this->assertTrue(
                $reflection->isSubclassOf(BaseTimelineProvider::class),
                "{$class} must extend BaseTimelineProvider.",
            );
        }
    }

    #[Test]
    public function registry_contains_all_providers_without_duplicates(): void
    {
        $registry = new TimelineProviderRegistry();

        $providers = array_map(
            static fn (object $provider): string => $provider::class,
            $registry->providers(),
        );

        sort($providers);

        $expected = $this->providerClasses();

        sort($expected);

        $this->assertSame($expected, $providers);
        $this->assertCount(
            count(array_unique($providers)),
            $providers,
            'TimelineProviderRegistry contains duplicate providers.',
        );
    }

    #[Test]
    public function aggregator_uses_registry_providers(): void
    {
        $registry = new TimelineProviderRegistry();

        $aggregator = new TimelineAggregatorService(
            $registry->providers(),
        );

        $reflection = new ReflectionClass($aggregator);

        $property = $reflection->getProperty('providers');
        $property->setAccessible(true);

        $providers = $property->getValue($aggregator);

        $this->assertCount(
            count($registry->providers()),
            $providers,
        );
    }

    #[Test]
    public function providers_do_not_instantiate_timeline_event_directly(): void
    {
        foreach ($this->providerFiles() as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents);

            $this->assertStringNotContainsString(
                'new TimelineEvent(',
                $contents,
                basename($file).' still instantiates TimelineEvent directly.',
            );
        }
    }

    #[Test]
    public function providers_do_not_implement_interface_directly(): void
    {
        foreach ($this->providerFiles() as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents);

            $this->assertStringNotContainsString(
                'implements TimelineProviderInterface',
                $contents,
                basename($file).' should extend BaseTimelineProvider.',
            );
        }
    }

    /**
     * @return array<int,string>
     */
    private function providerClasses(): array
    {
        $classes = [];

        foreach ($this->providerFiles() as $file) {
            $classes[] = 'App\\Services\\Dashboard\\Timeline\\Providers\\'
                .pathinfo($file, PATHINFO_FILENAME);
        }

        sort($classes);

        return $classes;
    }

    /**
     * @return array<int,string>
     */
    private function providerFiles(): array
    {
        $files = glob(app_path('Services/Dashboard/Timeline/Providers/*.php'));

        $this->assertNotFalse($files);

        sort($files);

        return $files;
    }
}
