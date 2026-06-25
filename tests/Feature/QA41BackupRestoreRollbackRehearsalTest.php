<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QA41BackupRestoreRollbackRehearsalTest extends TestCase
{
    public function test_disaster_recovery_runbooks_are_present(): void
    {
        foreach ([
            'docs/11-operacoes/disaster-recovery-rehearsal-runbook.md',
            'docs/11-operacoes/staging-restore-validation-checklist.md',
            'docs/11-operacoes/staging-rollback-validation-checklist.md',
            'docs/11-operacoes/backup-restore-runbook.md',
            'docs/11-operacoes/rollback-runbook.md',
        ] as $path) {
            $this->assertFileExists(base_path($path));
        }
    }

    public function test_dr_rehearsal_dry_run_command_is_sanitized_and_non_destructive(): void
    {
        $exitCode = Artisan::call('mvhab:operations:dr-rehearsal', ['--dry-run' => true, '--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('backup_runbook', $output);
        $this->assertStringContainsString('rollback_rehearsal', $output);
        $this->assertStringNotContainsString('APP_KEY', $output);
        $this->assertStringNotContainsString('DB_PASSWORD', $output);
        $this->assertStringNotContainsString('password=', strtolower($output));
        $this->assertStringNotContainsString('token=', strtolower($output));
    }

    public function test_dr_rehearsal_command_requires_dry_run_flag(): void
    {
        $exitCode = Artisan::call('mvhab:operations:dr-rehearsal');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('--dry-run', Artisan::output());
    }
}
