<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class IncidentRunbookTest extends TestCase
{
    public function test_incident_runbooks_cover_required_phase_3_scenarios(): void
    {
        $incident = (string) file_get_contents(base_path('docs/11-operacoes/incident-response-runbook.md'));
        $observability = (string) file_get_contents(base_path('docs/11-operacoes/observability-logging-runbook.md'));
        $support = (string) file_get_contents(base_path('docs/11-operacoes/support-escalation-runbook.md'));
        $raci = (string) file_get_contents(base_path('docs/11-operacoes/municipal-pilot-raci.md'));
        $drill = (string) file_get_contents(base_path('docs/11-operacoes/incident-drill-checklist.md'));

        $incidentLower = strtolower($incident);

        foreach (['SEV1', 'SEV2', 'SEV3', 'SEV4'] as $needle) {
            $this->assertStringContainsString($needle, $incident);
        }

        foreach (['documento privado exposto', 'job falhado', 'rollback'] as $needle) {
            $this->assertStringContainsString($needle, $incidentLower);
        }

        $this->assertStringContainsString('logs', strtolower($observability));
        $this->assertStringContainsString('failed jobs', strtolower($observability));
        $this->assertStringContainsString('suporte', strtolower($support));
        $this->assertStringContainsString('DPO', $raci);
        $this->assertStringContainsString('drill', strtolower($drill));
    }
}
