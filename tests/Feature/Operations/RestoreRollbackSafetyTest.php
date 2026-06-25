<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class RestoreRollbackSafetyTest extends TestCase
{
    public function test_restore_validation_checklist_requires_post_restore_smoke(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/staging-restore-validation-checklist.md'));

        foreach ([
            'ambiente nao produtivo',
            'homepage',
            'login',
            'documentos privados',
            'backoffice',
            'RGPD',
            'rollback',
        ] as $expected) {
            $this->assertStringContainsString($expected, $document);
        }
    }

    public function test_rollback_validation_checklist_requires_abort_criteria_and_smoke(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/staging-rollback-validation-checklist.md'));

        foreach ([
            'php artisan down',
            'git checkout <previous_release_ref>',
            'php artisan queue:restart',
            'smoke',
            'criterios para abortar',
            'documentos privados',
        ] as $expected) {
            $this->assertStringContainsString($expected, $document);
        }

        $this->assertStringNotContainsString('php artisan migrate:fresh --force', $document);
    }
}
