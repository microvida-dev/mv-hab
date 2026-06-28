<?php

namespace Tests\Feature\UX;

use App\Services\Analytics\SlaAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class SlaAnalyticsTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_sla_summary_buckets_overdue_and_due_soon_tasks(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $summary = app(SlaAnalyticsService::class)->summary($administrator, []);
        $labels = collect($summary['buckets'])->pluck('label')->all();

        $this->assertContains('Em atraso', $labels);
        $this->assertContains('A vencer', $labels);
    }
}
