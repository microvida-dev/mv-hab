<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\WorkloadAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class WorkloadAnalyticsServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_workload_service_returns_aggregated_responsible_rows(): void
    {
        $this->seedAccess();
        $user = $this->analyticsUser();
        $this->createAnalyticsFixtures($user);

        $rows = app(WorkloadAnalyticsService::class)->byResponsible($user, []);

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('total', $rows[0]);
    }
}
