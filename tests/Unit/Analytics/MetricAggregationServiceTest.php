<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\MetricAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class MetricAggregationServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_metric_aggregation_returns_executive_metric_cards(): void
    {
        $this->seedAccess();
        $user = $this->analyticsUser();
        $this->createAnalyticsFixtures($user);

        $metrics = app(MetricAggregationService::class)->executiveMetrics($user, []);

        $this->assertNotEmpty($metrics);
        $this->assertSame('Candidaturas recebidas', $metrics[0]['title']);
    }
}
