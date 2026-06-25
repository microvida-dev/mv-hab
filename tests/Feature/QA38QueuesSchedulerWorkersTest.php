<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QA38QueuesSchedulerWorkersTest extends TestCase
{
    public function test_queue_scheduler_runbooks_and_playbook_are_present(): void
    {
        $runbook = (string) file_get_contents(base_path('docs/11-operacoes/scheduler-queues-workers-runbook.md'));
        $playbook = (string) file_get_contents(base_path('docs/11-operacoes/queue-failed-jobs-playbook.md'));

        $this->assertStringContainsString('queue:work --sleep=3 --tries=3 --timeout=120', $runbook);
        $this->assertStringContainsString('queue:work --stop-when-empty', $runbook);
        $this->assertStringContainsString('queue:restart', $runbook);
        $this->assertStringContainsString('schedule:list', $runbook);
        $this->assertStringContainsString('sem tarefas agendadas atuais', $runbook);
        $this->assertStringContainsString('queue:failed', $playbook);
        $this->assertStringContainsString('queue:retry', $playbook);
        $this->assertStringContainsString('queue:forget', $playbook);
    }

    public function test_queue_health_command_runs_without_exposing_secrets(): void
    {
        $exitCode = Artisan::call('mvhab:operations:queue-health', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('queue_connection', $output);
        $this->assertStringNotContainsString('APP_KEY', $output);
        $this->assertStringNotContainsString('DB_PASSWORD', $output);
        $this->assertStringNotContainsString('token=', strtolower($output));
    }
}
