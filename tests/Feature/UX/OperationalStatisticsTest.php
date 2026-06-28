<?php

namespace Tests\Feature\UX;

use App\Services\Analytics\OperationalStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class OperationalStatisticsTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_operational_statistics_are_aggregated_by_domain(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $rows = app(OperationalStatisticsService::class)->operationsTable([]);

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('dominio', $rows[0]);
        $this->assertArrayNotHasKey('storage_path', $rows[0]);
    }
}
