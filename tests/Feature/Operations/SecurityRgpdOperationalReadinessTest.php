<?php

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityRgpdOperationalReadinessTest extends TestCase
{
    public function test_security_rgpd_checklist_documents_private_storage_audit_mfa_and_secrets(): void
    {
        $checklist = (string) file_get_contents(base_path('docs/11-operacoes/security-rgpd-operational-checklist.md'));

        foreach ([
            'storage/app/private',
            'controller/policy',
            'MFA',
            'auditoria',
            'RGPD',
            '.env',
            'APP_KEY',
            'DB_PASSWORD',
        ] as $expectedTerm) {
            $this->assertStringContainsString($expectedTerm, $checklist);
        }
    }

    public function test_private_storage_is_not_publicly_linked(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('qa36/private.txt', 'conteudo privado sintetico');

        $localRoot = config('filesystems.disks.local.root');
        $links = config('filesystems.links');

        $this->assertSame(app()->storagePath().'/app/private', $localRoot);
        $this->assertIsArray($links);
        $this->assertNotContains($localRoot, array_values($links));
        $this->assertContains($this->get('/storage/qa36/private.txt')->getStatusCode(), [403, 404]);
    }
}
