<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class ProductionConfigurationReadinessTest extends TestCase
{
    public function test_production_environment_expectations_are_documented_and_configurable(): void
    {
        $checklist = (string) file_get_contents(base_path('docs/11-operacoes/production-environment-checklist.md'));
        $gitignore = (string) file_get_contents(base_path('.gitignore'));
        $appConfig = (string) file_get_contents(base_path('config/app.php'));
        $queueConfig = (string) file_get_contents(base_path('config/queue.php'));

        $this->assertStringContainsString('APP_ENV', $checklist);
        $this->assertStringContainsString('production', $checklist);
        $this->assertStringContainsString('APP_DEBUG', $checklist);
        $this->assertStringContainsString('false', $checklist);
        $this->assertStringContainsString('APP_TIMEZONE', $checklist);
        $this->assertStringContainsString('Europe/Lisbon', $checklist);
        $this->assertStringContainsString('QUEUE_CONNECTION', $checklist);
        $this->assertStringContainsString('SESSION_DRIVER', $checklist);
        $this->assertStringContainsString('.env', $gitignore);
        $this->assertStringContainsString("env('APP_TIMEZONE', 'Europe/Lisbon')", $appConfig);
        $this->assertStringContainsString("env('QUEUE_CONNECTION', 'database')", $queueConfig);

        $this->assertFalse(
            config('app.env') === 'production' && config('app.debug') === true,
            'Ambiente production nao pode correr com debug ativo.',
        );
    }
}
