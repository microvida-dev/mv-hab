<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\TrendAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class TrendAnalysisServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_trend_service_returns_monthly_application_dataset(): void
    {
        $this->seedAccess();
        $this->createAnalyticsFixtures($this->analyticsUser());

        $dataset = app(TrendAnalysisService::class)->monthlyApplications([]);

        $this->assertSame('line', $dataset['type']);
        $this->assertCount(6, $dataset['items']);
    }
}
