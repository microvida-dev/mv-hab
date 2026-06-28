<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\ExecutiveDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class ExecutiveDashboardServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_executive_dashboard_service_returns_executive_sections(): void
    {
        $this->seedAccess();
        $user = $this->analyticsUser();
        $this->createAnalyticsFixtures($user);

        $payload = app(ExecutiveDashboardService::class)->build($user, []);

        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('trends', $payload);
        $this->assertArrayHasKey('sla', $payload);
    }
}
