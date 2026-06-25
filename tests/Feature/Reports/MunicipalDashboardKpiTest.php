<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\MunicipalKpiService;
use Tests\TestCase;

class MunicipalDashboardKpiTest extends TestCase
{
    public function test_municipal_kpi_catalog_contains_phase_3_operational_domains(): void
    {
        $service = app(MunicipalKpiService::class);
        $codes = collect($service->catalog())->pluck('code')->all();

        foreach ([
            'active_contests',
            'applications_by_status',
            'pending_documents',
            'visits_by_status',
            'tickets_by_status',
            'work_tasks_by_sla',
            'active_contracts',
            'rents_by_status',
            'rgpd_requests_by_status',
            'critical_audit_events',
        ] as $expectedCode) {
            $this->assertContains($expectedCode, $codes);
        }

        $this->assertContains('contest_id', $service->allowedFilters());
        $this->assertContains('sla', $service->allowedFilters());
    }
}
