<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardAnalyticsServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_dashboard_analytics_service_composes_read_only_payload(): void
    {
        $this->seedAccess();
        $user = $this->analyticsUser();
        $this->createAnalyticsFixtures($user);

        $payload = app(DashboardAnalyticsService::class)->forUser($user, []);

        $this->assertArrayHasKey('kpis', $payload);
        $this->assertArrayHasKey('funnel', $payload);
        $this->assertArrayHasKey('omitted_metrics', $payload);
    }
}
