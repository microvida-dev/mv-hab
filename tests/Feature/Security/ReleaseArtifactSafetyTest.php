<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReleaseArtifactSafetyTest extends TestCase
{
    public function test_release_artifact_script_rejects_env_with_real_values(): void
    {
        $directory = storage_path('framework/testing/release-artifact-safety');
        File::ensureDirectoryExists($directory);
        $artifact = $directory.'/.env.production';

        File::put($artifact, implode("\n", [
            'APP_KEY='.('base64:'.str_repeat('B', 44)),
            'APP_DEBUG'.'=true',
            'DB_PASSWORD='.'municipal-secret-value',
        ]));

        $process = new Process([PHP_BINARY, 'scripts/check-release-artifact-safety.php', $artifact], base_path());
        $process->run();

        $this->assertFalse($process->isSuccessful());
        $this->assertStringContainsString('env_file', $process->getOutput());
        $this->assertStringContainsString('app_key', $process->getOutput());
        $this->assertStringContainsString('app_debug_true', $process->getOutput());
        $this->assertStringContainsString('db_password', $process->getOutput());
    }

    public function test_release_artifact_script_allows_env_example_placeholders(): void
    {
        $directory = storage_path('framework/testing/release-artifact-safety');
        File::ensureDirectoryExists($directory);
        $artifact = $directory.'/.env.example';

        File::put($artifact, implode("\n", [
            'APP_KEY=',
            'APP_DEBUG=false',
            'DB_PASSWORD=<secret>',
        ]));

        $process = new Process([PHP_BINARY, 'scripts/check-release-artifact-safety.php', $artifact], base_path());
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
    }
}
