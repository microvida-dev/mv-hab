<?php

namespace App\Jobs;

use App\Services\Program53\Resilience\Program53FailureClassifier;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class GenerateApplicationResultExport implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public int $retryUntilTimestamp;

    public function __construct(
        public readonly int $reportExportId,
        ?int $retryUntilTimestamp = null,
    ) {
        $this->onQueue('reports');
        $this->retryUntilTimestamp = $retryUntilTimestamp
            ?? now()->addSeconds((int) config(
                'program53.exports.retry_window_seconds',
                7200,
            ))->getTimestamp();
    }

    public function uniqueId(): string
    {
        return 'application-result-export:'.$this->reportExportId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::createFromTimestampUTC(
            $this->retryUntilTimestamp,
        );
    }

    public function handle(
        TemporalApplicationResultExportService $exports,
        Program53FailureClassifier $failures,
    ): void {
        try {
            $exports->process($this->reportExportId);
        } catch (Throwable $exception) {
            if (! $failures->classify($exception)->retryable()) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        app(TemporalApplicationResultExportService::class)
            ->markFailed($this->reportExportId, $exception);
    }
}
