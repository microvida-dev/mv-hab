<?php

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchedulerQueueReadinessTest extends TestCase
{
    public function test_scheduler_and_queue_worker_runbook_exists_and_artisan_schedule_is_available(): void
    {
        $runbook = (string) file_get_contents(base_path('docs/11-operacoes/scheduler-queues-workers-runbook.md'));

        $this->assertStringContainsString('schedule:run', $runbook);
        $this->assertStringContainsString('schedule:list', $runbook);
        $this->assertStringContainsString('queue:work', $runbook);
        $this->assertStringContainsString('queue:restart', $runbook);
        $this->assertStringContainsString('failed jobs', strtolower($runbook));
        $this->assertStringContainsString('systemd', $runbook);
        $this->assertStringContainsString('supervisord', $runbook);

        $this->assertSame(0, Artisan::call('schedule:list'));
    }

    public function test_queue_configuration_supports_database_workers_and_failed_jobs(): void
    {
        $databaseQueue = config('queue.connections.database');
        $failedQueue = config('queue.failed');

        $this->assertIsArray($databaseQueue);
        $this->assertSame('database', $databaseQueue['driver'] ?? null);
        $this->assertSame('jobs', $databaseQueue['table'] ?? null);
        $this->assertIsArray($failedQueue);
        $this->assertSame('database-uuids', $failedQueue['driver'] ?? null);
        $this->assertSame('failed_jobs', $failedQueue['table'] ?? null);
    }
}
