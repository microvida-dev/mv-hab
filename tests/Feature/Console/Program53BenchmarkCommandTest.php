<?php

namespace Tests\Feature\Console;

use App\Data\Program53\Program53BenchmarkConfiguration;
use App\Enums\ApplicationResultExportFormat;
use App\Services\Program53\Benchmark\Program53BenchmarkEnvironment;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Program53BenchmarkCommandTest extends TestCase
{
    public function test_command_builds_valid_isolated_artifacts_and_cleans_runtime_data(): void
    {
        $output = 'storage/qa/program53-benchmark-command-test';
        $this->deleteReports($output);

        try {
            $exit = Artisan::call('program53:benchmark', [
                '--applications' => 30,
                '--analysts' => 2,
                '--municipalities' => 2,
                '--contests' => 2,
                '--seed' => 53_000_030,
                '--scenario' => 'command-test',
                '--formats' => 'csv,json,xml,xlsx',
                '--queue-workers' => 1,
                '--output' => $output,
                '--assert' => true,
                '--cleanup' => true,
            ]);

            $this->assertSame(0, $exit, Artisan::output());
            $json = json_decode(
                (string) file_get_contents(base_path($output.'.json')),
                true,
                flags: JSON_THROW_ON_ERROR,
            );
            $this->assertSame('pass', $json['result']);
            $this->assertSame(30, $json['counts']['applications']);
            $this->assertSame(30, $json['counts']['results']);
            $this->assertSame(30, $json['counts']['notifications']);
            $this->assertSame('database', $json['runtime']['queue_driver']);
            $this->assertTrue($json['integrity']['queries_bounded_by_chunks']);
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{64}$/',
                $json['artifacts']['package_sha256'],
            );
            $this->assertNotEmpty($json['query_plans']['workspace']);

            $configuration = new Program53BenchmarkConfiguration(
                applications: 30,
                analysts: 2,
                municipalities: 2,
                contests: 2,
                seed: 53_000_030,
                scenario: 'command-test',
                formats: [ApplicationResultExportFormat::Csv],
                queueWorkers: 1,
                output: $output,
                cleanup: true,
                assert: true,
            );
            $runId = app(Program53BenchmarkEnvironment::class)->runId($configuration);
            $this->assertDirectoryDoesNotExist(
                app(Program53BenchmarkEnvironment::class)->baseDirectory($runId),
            );
        } finally {
            $this->deleteReports($output);
        }
    }

    public function test_command_rejects_unsafe_output_path(): void
    {
        $exit = Artisan::call('program53:benchmark', [
            '--applications' => 10,
            '--municipalities' => 1,
            '--contests' => 1,
            '--output' => '../unsafe',
        ]);

        $this->assertSame(2, $exit);
        $this->assertStringContainsString('storage/qa', Artisan::output());
    }

    private function deleteReports(string $base): void
    {
        @unlink(base_path($base.'.json'));
        @unlink(base_path($base.'.md'));
    }
}
