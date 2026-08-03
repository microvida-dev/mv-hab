<?php

namespace Tests\Unit\Program53;

use App\Jobs\DeliverProceduralEmail;
use App\Jobs\ExpireApplicationResultExport;
use App\Jobs\GenerateApplicationResultExport;
use Tests\TestCase;

final class Program53QueueHardeningTest extends TestCase
{
    public function test_report_jobs_have_bounded_retry_timeout_and_backoff(): void
    {
        $generation = new GenerateApplicationResultExport(10);
        $expiration = new ExpireApplicationResultExport(10);

        $this->assertSame('reports', $generation->queue);
        $this->assertSame(3, $generation->tries);
        $this->assertSame(1800, $generation->timeout);
        $this->assertSame([60, 300, 900], $generation->backoff());
        $this->assertGreaterThan(now(), $generation->retryUntil());
        $this->assertSame('reports', $expiration->queue);
        $this->assertSame(3, $expiration->tries);
        $this->assertSame(120, $expiration->timeout);
        $this->assertSame([30, 120, 600], $expiration->backoff());
        $this->assertGreaterThan(now(), $expiration->retryUntil());
        $this->assertGreaterThan(
            $generation->timeout,
            (int) config('queue.connections.database.retry_after'),
        );
    }

    public function test_notification_retry_deadline_is_stable_for_serialized_job(): void
    {
        $job = new DeliverProceduralEmail(10, 20);
        $first = $job->retryUntil()->getTimestamp();

        $restored = unserialize(serialize($job));

        $this->assertInstanceOf(DeliverProceduralEmail::class, $restored);
        $this->assertSame($first, $restored->retryUntil()->getTimestamp());
        $this->assertSame([60, 300, 900, 3600], $restored->backoff());
    }
}
