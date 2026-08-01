<?php

namespace App\Jobs;

use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
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

    public function __construct(public readonly int $reportExportId)
    {
        $this->onQueue('reports');
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

    public function handle(TemporalApplicationResultExportService $exports): void
    {
        $exports->process($this->reportExportId);
    }

    public function failed(Throwable $exception): void
    {
        app(TemporalApplicationResultExportService::class)
            ->markFailed($this->reportExportId, $exception);
    }
}
