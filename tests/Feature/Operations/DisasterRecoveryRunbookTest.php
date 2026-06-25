<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class DisasterRecoveryRunbookTest extends TestCase
{
    public function test_backup_restore_runbook_covers_private_storage_and_checksums(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/backup-restore-runbook.md'));

        $this->assertStringContainsString('mysqldump --single-transaction', $document);
        $this->assertStringContainsString('storage/app/private', $document);
        $this->assertStringContainsString('sha256', $document);
        $this->assertStringContainsString('Nunca guardar backup real no Git', $document);
        $this->assertStringContainsString('ambiente isolado', $document);
    }

    public function test_disaster_recovery_rehearsal_runbook_blocks_real_data_and_destructive_commands(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/disaster-recovery-rehearsal-runbook.md'));

        $this->assertStringContainsString('ambiente nao produtivo', $document);
        $this->assertStringContainsString('nao usar dados reais', $document);
        $this->assertStringContainsString('Nunca executar migrate:fresh', $document);
        $this->assertStringContainsString('mvhab:operations:dr-rehearsal --dry-run', $document);
        $this->assertStringNotContainsString('php artisan migrate:fresh --force', $document);
    }
}
