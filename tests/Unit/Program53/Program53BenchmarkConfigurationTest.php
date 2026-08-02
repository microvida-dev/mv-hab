<?php

namespace Tests\Unit\Program53;

use App\Data\Program53\Program53BenchmarkConfiguration;
use App\Enums\ApplicationResultExportFormat;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Program53BenchmarkConfigurationTest extends TestCase
{
    public function test_valid_configuration_is_serialized_without_secrets(): void
    {
        $configuration = $this->configuration();

        $this->assertSame(1_000, $configuration->applications);
        $this->assertSame(
            ['csv', 'json', 'xml', 'xlsx'],
            $configuration->toArray()['formats'],
        );
        $this->assertArrayNotHasKey('password', $configuration->toArray());
    }

    #[DataProvider('invalidConfigurations')]
    public function test_invalid_or_unsafe_configuration_fails_closed(
        int $applications,
        int $analysts,
        int $municipalities,
        int $contests,
        int $workers,
        string $scenario,
        ?string $output,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration(
            applications: $applications,
            analysts: $analysts,
            municipalities: $municipalities,
            contests: $contests,
            workers: $workers,
            scenario: $scenario,
            output: $output,
        );
    }

    /** @return iterable<string, array{int, int, int, int, int, string, string|null}> */
    public static function invalidConfigurations(): iterable
    {
        yield 'zero applications' => [0, 4, 2, 2, 1, 'smoke', null];
        yield 'too many applications' => [50_001, 4, 2, 2, 1, 'smoke', null];
        yield 'no analysts' => [1_000, 0, 2, 2, 1, 'smoke', null];
        yield 'contest without application' => [1, 1, 2, 2, 1, 'smoke', null];
        yield 'sync-like no worker' => [1_000, 4, 2, 2, 0, 'smoke', null];
        yield 'unsafe scenario' => [1_000, 4, 2, 2, 1, '../prod', null];
        yield 'unsafe output' => [1_000, 4, 2, 2, 1, 'smoke', '../report'];
        yield 'outside qa' => [1_000, 4, 2, 2, 1, 'smoke', 'docs/report'];
    }

    private function configuration(
        int $applications = 1_000,
        int $analysts = 4,
        int $municipalities = 2,
        int $contests = 2,
        int $workers = 1,
        string $scenario = 'smoke',
        ?string $output = 'storage/qa/program53-test',
    ): Program53BenchmarkConfiguration {
        return new Program53BenchmarkConfiguration(
            applications: $applications,
            analysts: $analysts,
            municipalities: $municipalities,
            contests: $contests,
            seed: 53_001_000,
            scenario: $scenario,
            formats: ApplicationResultExportFormat::cases(),
            queueWorkers: $workers,
            output: $output,
            cleanup: true,
            assert: true,
        );
    }
}
