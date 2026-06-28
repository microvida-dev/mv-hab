<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\TerritorialDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class TerritorialDistributionServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_territorial_service_returns_bar_dataset(): void
    {
        $this->seedAccess();
        $this->createAnalyticsFixtures($this->analyticsUser());

        $dataset = app(TerritorialDistributionService::class)->applicationsByParish([]);

        $this->assertSame('bar', $dataset['type']);
        $this->assertNotEmpty($dataset['items']);
    }
}
