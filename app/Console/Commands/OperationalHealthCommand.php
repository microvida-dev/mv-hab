<?php

namespace App\Console\Commands;

use App\Services\Operations\OperationalHealthService;
use Illuminate\Console\Command;

class OperationalHealthCommand extends Command
{
    protected $signature = 'mvhab:operations:health {--json : Emitir resultado em JSON sanitizado}';

    protected $description = 'Validate operational readiness without exposing secrets or personal data.';

    public function handle(OperationalHealthService $health): int
    {
        $checks = $health->checks();

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['check', 'status', 'message'],
                array_map(fn (array $check): array => [$check['name'], $check['status'], $check['message']], $checks),
            );
        }

        return $health->hasBlockingFailures() ? self::FAILURE : self::SUCCESS;
    }
}
