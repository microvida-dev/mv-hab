<?php

namespace Tests\Feature\Reports;

use App\Models\Application;
use App\Services\Reports\MunicipalKpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MunicipalReportsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_kpis_use_aggregate_payloads_instead_of_model_rows(): void
    {
        Application::factory()->count(20)->submitted()->create();

        DB::enableQueryLog();
        $snapshot = app(MunicipalKpiService::class)->snapshot();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertIsArray($snapshot['kpis']['applications_by_status']);
        $this->assertArrayHasKey('submitted', $snapshot['kpis']['applications_by_status']);
        $this->assertLessThan(120, count($queries), 'O snapshot municipal deve manter volume de queries controlado e independente de linhas.');
        $this->assertStringNotContainsString('candidate_notes', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }
}
