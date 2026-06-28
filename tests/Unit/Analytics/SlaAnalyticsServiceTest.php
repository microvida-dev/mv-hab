<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\SlaAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class SlaAnalyticsServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_sla_service_returns_buckets_and_compliance_rate(): void
    {
        $this->seedAccess();
        $user = $this->analyticsUser();
        $this->createAnalyticsFixtures($user);

        $summary = app(SlaAnalyticsService::class)->summary($user, []);

        $this->assertArrayHasKey('buckets', $summary);
        $this->assertArrayHasKey('compliance_rate', $summary);
    }
}
