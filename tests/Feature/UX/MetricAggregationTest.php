<?php

namespace Tests\Feature\UX;

use App\Services\Analytics\MetricAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class MetricAggregationTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_metric_service_returns_aggregated_kpis_without_pii(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $metrics = app(MetricAggregationService::class)->executiveMetrics($administrator, []);
        $payload = json_encode($metrics, JSON_THROW_ON_ERROR);

        $this->assertStringContainsString('Candidaturas recebidas', $payload);
        $this->assertStringNotContainsString('@', $payload);
        $this->assertStringNotContainsString('storage_path', $payload);
    }
}
