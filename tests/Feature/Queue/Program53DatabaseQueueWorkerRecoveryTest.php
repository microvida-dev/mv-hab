<?php

namespace Tests\Feature\Queue;

use Illuminate\Support\Str;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class Program53DatabaseQueueWorkerRecoveryTest extends TestCase
{
    public function test_killed_database_worker_releases_job_for_idempotent_retry(): void
    {
        if (! function_exists('proc_open') || PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('O gate exige processos POSIX independentes.');
        }

        $directory = storage_path('qa/program53-queue-'.Str::uuid());
        $database = $directory.'/queue.sqlite';
        $marker = $directory.'/worker';
        mkdir($directory, 0700, true);
        touch($database);
        $environment = $this->workerEnvironment($database);

        try {
            $this->runProcess([
                PHP_BINARY,
                'artisan',
                'migrate:fresh',
                '--force',
                '--no-interaction',
            ], $environment, 180);
            $this->runProcess([
                PHP_BINARY,
                'tests/Support/Queue/dispatch-interruptible-program53-job.php',
                $marker,
            ], $environment);

            $firstWorker = new Process([
                PHP_BINARY,
                'artisan',
                'queue:work',
                'database',
                '--queue=reports',
                '--once',
                '--sleep=1',
                '--tries=3',
                '--timeout=3',
                '--no-interaction',
            ], base_path(), $environment);
            $firstWorker->setTimeout(30);
            $firstWorker->start();
            $this->waitForMarker($marker.'.started', $firstWorker);
            $firstWorker->stop(0, 9);

            sleep(6);

            $this->runProcess([
                PHP_BINARY,
                'artisan',
                'queue:work',
                'database',
                '--queue=reports',
                '--once',
                '--sleep=1',
                '--tries=3',
                '--timeout=3',
                '--no-interaction',
            ], $environment, 30);

            $started = file($marker.'.started', FILE_IGNORE_NEW_LINES);
            $completed = file($marker.'.completed', FILE_IGNORE_NEW_LINES);
            $pdo = new PDO('sqlite:'.$database);

            $this->assertIsArray($started);
            $this->assertCount(2, $started);
            $this->assertSame(['completed'], $completed);
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn());
            $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM failed_jobs')->fetchColumn());
        } finally {
            $this->deleteDirectory($directory);
        }
    }

    /** @return array<string, string> */
    private function workerEnvironment(string $database): array
    {
        return [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'file',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_QUEUE_RETRY_AFTER' => '5',
            'DB_URL' => '',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'database',
            'SESSION_DRIVER' => 'array',
        ];
    }

    /** @param list<string> $command
     * @param  array<string, string>  $environment
     */
    private function runProcess(
        array $command,
        array $environment,
        int $timeout = 60,
    ): void {
        $process = new Process($command, base_path(), $environment);
        $process->setTimeout($timeout);
        $process->mustRun();
    }

    private function waitForMarker(string $path, Process $worker): void
    {
        $deadline = microtime(true) + 20;
        while (! is_file($path) && microtime(true) < $deadline) {
            if (! $worker->isRunning()) {
                $this->fail(
                    'O primeiro worker terminou antes do checkpoint: '
                    .$worker->getErrorOutput(),
                );
            }
            usleep(50_000);
        }

        $this->assertFileExists($path);
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($directory);
    }
}
