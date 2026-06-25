<?php

namespace Tests\Unit\Operations;

use App\Services\Operations\QueueHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_health_service_allows_sync_only_for_testing_environment(): void
    {
        config([
            'app.env' => 'testing',
            'queue.default' => 'sync',
        ]);

        $checks = app(QueueHealthService::class)->checks();
        $queueCheck = collect($checks)->firstWhere('name', 'queue_connection');

        $this->assertSame('warn', $queueCheck['status'] ?? null);
        $this->assertFalse(app(QueueHealthService::class)->hasBlockingFailures());
    }

    public function test_queue_health_service_fails_sync_in_production(): void
    {
        config([
            'app.env' => 'production',
            'queue.default' => 'sync',
        ]);

        $checks = app(QueueHealthService::class)->checks();
        $queueCheck = collect($checks)->firstWhere('name', 'queue_connection');

        $this->assertSame('fail', $queueCheck['status'] ?? null);
        $this->assertTrue(app(QueueHealthService::class)->hasBlockingFailures());
    }

    public function test_queue_health_service_validates_database_queue_tables(): void
    {
        config(['queue.default' => 'database']);

        $checks = collect(app(QueueHealthService::class)->checks());

        $this->assertSame('pass', $checks->firstWhere('name', 'queue_connection')['status'] ?? null);
        $this->assertSame('pass', $checks->firstWhere('name', 'jobs_table')['status'] ?? null);
        $this->assertSame('pass', $checks->firstWhere('name', 'failed_jobs_table')['status'] ?? null);
        $this->assertSame('pass', $checks->firstWhere('name', 'job_batches_table')['status'] ?? null);
    }
}
