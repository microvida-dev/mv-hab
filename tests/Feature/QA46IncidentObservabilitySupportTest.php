<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QA46IncidentObservabilitySupportTest extends TestCase
{
    public function test_qa46_operational_health_command_runs_without_exposing_secrets(): void
    {
        Artisan::call('mvhab:operations:health', ['--json' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('database_connection', $output);
        $this->assertStringContainsString('failed_jobs', $output);
        $this->assertStringNotContainsString('APP_KEY', $output);
        $this->assertStringNotContainsString('DB_PASSWORD', $output);
        $this->assertStringNotContainsString('token=', strtolower($output));
    }

    public function test_qa46_incident_response_documentation_exists_for_pilot_drills(): void
    {
        foreach ([
            'docs/11-operacoes/incident-response-runbook.md',
            'docs/11-operacoes/observability-logging-runbook.md',
            'docs/11-operacoes/support-escalation-runbook.md',
            'docs/11-operacoes/municipal-pilot-raci.md',
            'docs/11-operacoes/incident-drill-checklist.md',
        ] as $path) {
            $this->assertFileExists(base_path($path));
        }
    }
}
