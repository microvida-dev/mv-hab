<?php

namespace App\Services\Operations;

class DisasterRecoveryChecklistService
{
    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function dryRunChecks(): array
    {
        return [
            ...$this->requiredRunbookChecks(),
            $this->backupRunbookCheck(),
            $this->restoreRunbookCheck(),
            $this->rollbackRunbookCheck(),
            $this->smokeChecklistCheck(),
            $this->destructiveCommandGuardCheck(),
        ];
    }

    public function hasBlockingFailures(): bool
    {
        foreach ($this->dryRunChecks() as $check) {
            if ($check['status'] === 'fail') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    private function requiredRunbookChecks(): array
    {
        $checks = [];

        foreach ($this->requiredRunbooks() as $path) {
            $checks[] = $this->documentExistsCheck($path);
        }

        return $checks;
    }

    /**
     * @return list<string>
     */
    private function requiredRunbooks(): array
    {
        return [
            'docs/11-operacoes/backup-restore-runbook.md',
            'docs/11-operacoes/rollback-runbook.md',
            'docs/11-operacoes/deploy-runbook.md',
            'docs/11-operacoes/disaster-recovery-rehearsal-runbook.md',
            'docs/11-operacoes/staging-restore-validation-checklist.md',
            'docs/11-operacoes/staging-rollback-validation-checklist.md',
            'docs/11-operacoes/municipal-smoke-test-checklist.md',
        ];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function backupRunbookCheck(): array
    {
        $content = $this->read('docs/11-operacoes/backup-restore-runbook.md');

        return $this->containsAll($content, [
            'mysqldump --single-transaction',
            'storage/app/private',
            '.env',
            'sha256',
            'Nunca guardar backup real no Git',
        ])
            ? $this->check('backup_runbook', 'pass', 'Backup de DB, storage privado, configuracao e checksums documentado.')
            : $this->check('backup_runbook', 'fail', 'Runbook de backup incompleto para staging municipal.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function restoreRunbookCheck(): array
    {
        $content = $this->read('docs/11-operacoes/staging-restore-validation-checklist.md')
            .$this->read('docs/11-operacoes/backup-restore-runbook.md');

        return $this->containsAll($content, [
            'ambiente nao produtivo',
            'php artisan down',
            'php artisan optimize:clear',
            'php artisan route:list --except-vendor',
            'smoke',
            'documentos privados',
        ])
            ? $this->check('restore_rehearsal', 'pass', 'Restore rehearsal documentado apenas para ambiente nao produtivo.')
            : $this->check('restore_rehearsal', 'fail', 'Checklist de restore nao cobre isolamento, smoke e documentos privados.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function rollbackRunbookCheck(): array
    {
        $content = $this->read('docs/11-operacoes/staging-rollback-validation-checklist.md')
            .$this->read('docs/11-operacoes/rollback-runbook.md');

        return $this->containsAll($content, [
            'php artisan down',
            'git checkout <previous_release_ref>',
            'php artisan queue:restart',
            'smoke',
            'criterios',
            'Nunca usar',
        ])
            ? $this->check('rollback_rehearsal', 'pass', 'Rollback documentado com maintenance mode, release anterior, workers e smoke.')
            : $this->check('rollback_rehearsal', 'fail', 'Checklist de rollback incompleta.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function smokeChecklistCheck(): array
    {
        $content = $this->read('docs/11-operacoes/municipal-smoke-test-checklist.md');

        return $this->containsAll($content, [
            'homepage',
            'concursos',
            'login',
            'candidatura',
            'documentos privados',
            'backoffice',
            'visitas',
            'tickets',
            'FAQ',
            'RGPD',
        ])
            ? $this->check('municipal_smoke', 'pass', 'Smoke municipal cobre fluxos publicos, autenticados e RGPD.')
            : $this->check('municipal_smoke', 'fail', 'Smoke municipal nao cobre fluxos minimos.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function destructiveCommandGuardCheck(): array
    {
        $content = $this->read('docs/11-operacoes/rollback-runbook.md')
            .$this->read('docs/11-operacoes/deploy-runbook.md')
            .$this->read('docs/11-operacoes/disaster-recovery-rehearsal-runbook.md');

        if (! str_contains($content, 'migrate:fresh')) {
            return $this->check('destructive_command_guard', 'fail', 'Runbooks devem proibir explicitamente migrate:fresh em dados reais.');
        }

        if (str_contains(strtolower($content), 'php artisan migrate:fresh --force')) {
            return $this->check('destructive_command_guard', 'fail', 'Runbook contem comando destrutivo com --force.');
        }

        return $this->check('destructive_command_guard', 'pass', 'Runbooks proíbem comandos destrutivos e nao executam migrate:fresh.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function documentExistsCheck(string $path): array
    {
        return is_file(base_path($path))
            ? $this->check('runbook:'.$path, 'pass', $path.' existe.')
            : $this->check('runbook:'.$path, 'fail', $path.' em falta.');
    }

    private function read(string $path): string
    {
        $fullPath = base_path($path);

        if (! is_file($fullPath)) {
            return '';
        }

        $content = file_get_contents($fullPath);

        return is_string($content) ? $content : '';
    }

    /**
     * @param  list<string>  $needles
     */
    private function containsAll(string $content, array $needles): bool
    {
        $haystack = strtolower($content);

        foreach ($needles as $needle) {
            if (! str_contains($haystack, strtolower($needle))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function check(string $name, string $status, string $message): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
