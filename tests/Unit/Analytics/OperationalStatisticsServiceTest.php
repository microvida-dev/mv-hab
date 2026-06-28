<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\OperationalStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class OperationalStatisticsServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_operational_statistics_service_returns_contest_aggregates(): void
    {
        $this->seedAccess();
        $this->createAnalyticsFixtures($this->analyticsUser());

        $rows = app(OperationalStatisticsService::class)->applicationsByContest([]);

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('concurso', $rows[0]);
    }
}
