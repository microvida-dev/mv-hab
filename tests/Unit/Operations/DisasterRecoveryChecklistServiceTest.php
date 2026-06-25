<?php

namespace Tests\Unit\Operations;

use App\Services\Operations\DisasterRecoveryChecklistService;
use Tests\TestCase;

class DisasterRecoveryChecklistServiceTest extends TestCase
{
    public function test_dry_run_checks_return_no_blocking_failures_when_runbooks_are_complete(): void
    {
        $service = new DisasterRecoveryChecklistService;
        $checks = $service->dryRunChecks();

        $this->assertNotEmpty($checks);
        $this->assertFalse($service->hasBlockingFailures());
        $this->assertContains('backup_runbook', collect($checks)->pluck('name')->all());
        $this->assertContains('destructive_command_guard', collect($checks)->pluck('name')->all());
    }
}
