<?php

namespace App\Jobs;

use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExpireApplicationResultExport implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $reportExportId)
    {
        $this->onQueue('reports');
    }

    public function uniqueId(): string
    {
        return 'expire-application-result-export:'.$this->reportExportId;
    }

    public function handle(TemporalApplicationResultExportService $exports): void
    {
        $exports->expire($this->reportExportId);
    }
}
