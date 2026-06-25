<?php

namespace App\Console\Commands;

use App\Services\Operations\DisasterRecoveryChecklistService;
use Illuminate\Console\Command;

class DisasterRecoveryRehearsalCommand extends Command
{
    protected $signature = 'mvhab:operations:dr-rehearsal {--dry-run : Validar runbooks e checks sem executar restore/rollback} {--json : Emitir resultado sanitizado em JSON}';

    protected $description = 'Validate disaster recovery, restore and rollback readiness without destructive operations.';

    public function handle(DisasterRecoveryChecklistService $service): int
    {
        if (! $this->option('dry-run')) {
            $this->error('Este comando so suporta execucao segura com --dry-run.');

            return self::FAILURE;
        }

        $checks = $service->dryRunChecks();

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['check', 'status', 'message'],
                array_map(fn (array $check): array => [$check['name'], $check['status'], $check['message']], $checks),
            );
        }

        return $service->hasBlockingFailures() ? self::FAILURE : self::SUCCESS;
    }
}
