<?php

namespace Tests\Feature\UX;

use App\Services\Analytics\WorkloadAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class WorkloadAnalyticsTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_workload_summary_uses_staff_aggregates_only(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $workload = app(WorkloadAnalyticsService::class)->byResponsible($administrator, []);

        $this->assertNotEmpty($workload);
        $this->assertArrayHasKey('team', $workload[0]);
        $this->assertArrayNotHasKey('nif', $workload[0]);
    }
}
