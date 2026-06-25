<?php

namespace Tests\Feature;

use Tests\TestCase;

class QA36MunicipalProductionNoExternalIntegrationsTest extends TestCase
{
    public function test_qa36_operational_documents_exist_with_release_decision_scope_and_runbooks(): void
    {
        foreach ([
            'docs/08-qa/qa-36-production-municipal-final-report.md',
            'docs/11-operacoes/deploy-runbook.md',
            'docs/11-operacoes/rollback-runbook.md',
            'docs/11-operacoes/backup-restore-runbook.md',
            'docs/11-operacoes/municipal-admin-guide.md',
            'docs/11-operacoes/out-of-scope-integrations.md',
            'docs/11-operacoes/production-environment-checklist.md',
            'docs/11-operacoes/scheduler-queues-workers-runbook.md',
            'docs/11-operacoes/municipal-smoke-test-checklist.md',
            'docs/11-operacoes/security-rgpd-operational-checklist.md',
        ] as $path) {
            $this->assertFileExists(base_path($path), "{$path} deve existir.");
        }

        $report = (string) file_get_contents(base_path('docs/08-qa/qa-36-production-municipal-final-report.md'));

        $this->assertStringContainsString('Out of scope by municipal decision', $report);
        $this->assertStringContainsString('READY_FOR_STAGING_NOT_PRODUCTION', $report);
        $this->assertStringContainsString('rendas com gestão administrativa/manual', $report);
        $this->assertStringContainsString('IA documental assistiva sem decisão automática', $report);
        $this->assertStringContainsString('docs/11-operacoes/deploy-runbook.md', $report);
    }

    public function test_deploy_backup_restore_and_rollback_runbooks_prohibit_destructive_or_sensitive_practices(): void
    {
        $combined = collect([
            'docs/11-operacoes/deploy-runbook.md',
            'docs/11-operacoes/rollback-runbook.md',
            'docs/11-operacoes/backup-restore-runbook.md',
        ])->map(fn (string $path): string => (string) file_get_contents(base_path($path)))->implode("\n");

        $this->assertStringContainsString('php artisan migrate --force', $combined);
        $this->assertStringContainsString('php artisan queue:restart', $combined);
        $this->assertStringContainsString('php artisan route:list --except-vendor', $combined);
        $this->assertStringContainsString('mysqldump --single-transaction', $combined);
        $this->assertStringContainsString('Nunca usar `migrate:fresh` em dados reais', $combined);
        $this->assertStringContainsString('Nunca guardar backup real no Git', $combined);
        $this->assertStringContainsString('storage/app/private', $combined);
    }
}
