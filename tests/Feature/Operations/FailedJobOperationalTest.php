<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class FailedJobOperationalTest extends TestCase
{
    public function test_failed_job_playbook_and_queue_configuration_remain_operational(): void
    {
        $playbook = (string) file_get_contents(base_path('docs/11-operacoes/queue-failed-jobs-playbook.md'));

        $this->assertSame('database-uuids', config('queue.failed.driver'));
        $this->assertStringContainsString('queue:failed', $playbook);
        $this->assertStringContainsString('queue:retry', $playbook);
        $this->assertStringContainsString('queue:forget', $playbook);
        $this->assertStringNotContainsString('APP_KEY', $playbook);
        $this->assertStringNotContainsString('DB_PASSWORD', $playbook);
    }
}
