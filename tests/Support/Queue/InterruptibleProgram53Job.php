<?php

namespace Tests\Support\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class InterruptibleProgram53Job implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3;

    public function __construct(public readonly string $markerPath)
    {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        file_put_contents(
            $this->markerPath.'.started',
            getmypid().PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
        sleep(2);
        file_put_contents(
            $this->markerPath.'.completed',
            'completed'.PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }
}
