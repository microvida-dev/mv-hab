<?php

namespace Tests\Unit\Security;

use App\Services\Security\SecretPatternScanner;
use Tests\TestCase;

class SecretPatternScannerTest extends TestCase
{
    public function test_scanner_blocks_release_secret_patterns(): void
    {
        $scanner = new SecretPatternScanner;

        $content = implode("\n", [
            'APP_KEY='.('base64:'.str_repeat('A', 44)),
            'APP_DEBUG'.'=true',
            'DB_PASSWORD='.'super-secret-value',
            '-----BEGIN '.'PRIVATE KEY-----',
            'API_TOKEN='.'abcdef1234567890abcdef123456',
            '/'.'Users/demo/app',
            'storage_path'.'(',
            '123456789',
            '12345678901',
            'PT50'.str_repeat('1', 21),
            'Rua Ficticia 12',
        ]);

        $codes = collect($scanner->scanContent($content, 'artifact.txt'))->pluck('code')->all();

        $this->assertContains('app_key', $codes);
        $this->assertContains('app_debug_true', $codes);
        $this->assertContains('db_password', $codes);
        $this->assertContains('private_key', $codes);
        $this->assertContains('token', $codes);
        $this->assertContains('local_user_path', $codes);
        $this->assertContains('storage_path', $codes);
        $this->assertContains('nif_like', $codes);
        $this->assertContains('niss_like', $codes);
        $this->assertContains('iban_pt_like', $codes);
        $this->assertContains('address_like', $codes);
    }

    public function test_env_example_and_placeholders_are_allowed(): void
    {
        $scanner = new SecretPatternScanner;

        $this->assertSame([], $scanner->scanPath('.env.example'));
        $this->assertSame([], $scanner->scanContent("DB_PASSWORD=<secret>\nAPP_DEBUG=false", '.env.example'));
    }

    public function test_env_files_and_blocked_artifact_paths_are_rejected(): void
    {
        $scanner = new SecretPatternScanner;

        $codes = collect([
            ...$scanner->scanPath('.env.production'),
            ...$scanner->scanPath('storage/app/private/document.pdf'),
            ...$scanner->scanPath('backup.sql'),
            ...$scanner->scanPath('exports/candidates.zip'),
        ])->pluck('code')->all();

        $this->assertContains('env_file', $codes);
        $this->assertContains('private_storage_path', $codes);
        $this->assertContains('blocked_artifact_extension', $codes);
        $this->assertContains('export_or_backup_path', $codes);
    }
}
