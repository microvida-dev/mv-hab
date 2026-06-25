<?php

namespace App\Console\Commands;

use App\Services\Operations\QueueHealthService;
use Illuminate\Console\Command;

class QueueHealthCommand extends Command
{
    protected $signature = 'mvhab:operations:queue-health {--json : Emitir resultado em JSON sanitizado}';

    protected $description = 'Validate queue, failed jobs and worker storage readiness without exposing secrets.';

    public function handle(QueueHealthService $health): int
    {
        $checks = $health->checks();

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $rows = [];

            foreach ($checks as $check) {
                $rows[] = [$check['name'], $check['status'], $check['message']];
            }

            $this->table(['check', 'status', 'message'], $rows);
        }

        return $health->hasBlockingFailures() ? self::FAILURE : self::SUCCESS;
    }
}
