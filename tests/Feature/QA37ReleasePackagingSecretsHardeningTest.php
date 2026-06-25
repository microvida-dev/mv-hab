<?php

namespace Tests\Feature;

use Tests\TestCase;

class QA37ReleasePackagingSecretsHardeningTest extends TestCase
{
    public function test_release_packaging_safety_assets_are_present(): void
    {
        $this->assertFileExists(base_path('scripts/check-release-artifact-safety.php'));
        $this->assertFileExists(base_path('scripts/check-secrets.php'));
        $this->assertFileExists(base_path('docs/11-operacoes/release-packaging-safety.md'));
    }

    public function test_gitignore_blocks_sensitive_release_artifacts(): void
    {
        $gitignore = (string) file_get_contents(base_path('.gitignore'));

        foreach ([
            '.env',
            '.env.*',
            '!.env.example',
            '/storage/app/private/',
            '/storage/framework/',
            '/storage/logs/',
            '/storage/phpstan/',
            '/backups/',
            '*.sql',
            '*.dump',
            '*.tar',
            '*.tar.gz',
            '*.zip',
            '*.key',
            '*.pem',
        ] as $pattern) {
            $this->assertStringContainsString($pattern, $gitignore);
        }
    }

    public function test_release_packaging_documentation_declares_blocking_controls(): void
    {
        $document = (string) file_get_contents(base_path('docs/11-operacoes/release-packaging-safety.md'));

        $this->assertStringContainsString('.env.example', $document);
        $this->assertStringContainsString('APP_KEY', $document);
        $this->assertStringContainsString('debug ativo', $document);
        $this->assertStringContainsString('dumps SQL', $document);
        $this->assertStringContainsString('documentos reais', $document);
        $this->assertStringContainsString('php scripts/check-release-artifact-safety.php', $document);
    }
}
