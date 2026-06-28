<?php

namespace Tests\Feature\UX;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_analytics_dashboard_uses_bounded_aggregate_queries(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);
        Application::factory()->submitted()->count(12)->create();

        DB::enableQueryLog();

        $this->actingAs($administrator)
            ->get(route('backoffice.analytics.index'))
            ->assertOk();

        $this->assertLessThan(600, count(DB::getQueryLog()));
    }
}
