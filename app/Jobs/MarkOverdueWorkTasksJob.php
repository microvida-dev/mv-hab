<?php

namespace App\Jobs;

use App\Services\Workflows\WorkTaskSlaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarkOverdueWorkTasksJob implements ShouldQueue
{
    use Queueable;

    public function handle(WorkTaskSlaService $slaService): void
    {
        $slaService->dispatchDueSoonEvents();
        $slaService->markOverdue();
    }
}
