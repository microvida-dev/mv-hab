<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\MunicipalKpiService;
use Tests\TestCase;

class MunicipalKpiServiceTest extends TestCase
{
    public function test_filters_are_minimized_to_supported_report_dimensions(): void
    {
        $snapshot = app(MunicipalKpiService::class)->snapshot([
            'contest_id' => 10,
            'status' => 'submitted',
            'email' => 'pessoa@example.test',
            'internal_notes' => 'nao deve entrar',
        ]);

        $this->assertSame([
            'contest_id' => 10,
            'status' => 'submitted',
        ], $snapshot['filters']);
    }
}
