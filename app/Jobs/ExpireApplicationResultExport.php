<?php

namespace App\Jobs;

use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ExpireApplicationResultExport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $retryUntilTimestamp;

    public function __construct(
        public readonly int $reportExportId,
        ?int $retryUntilTimestamp = null,
    ) {
        $this->onQueue('reports');
        $this->retryUntilTimestamp = $retryUntilTimestamp
            ?? now()->addHours(2)->getTimestamp();
    }

    public function uniqueId(): string
    {
        return 'expire-application-result-export:'.$this->reportExportId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::createFromTimestampUTC(
            $this->retryUntilTimestamp,
        );
    }

    public function handle(TemporalApplicationResultExportService $exports): void
    {
        $exports->expire($this->reportExportId);
    }

    public function failed(Throwable $exception): void
    {
        app(TemporalApplicationResultExportService::class)
            ->markExpirationFailed($this->reportExportId, $exception);
    }
}
