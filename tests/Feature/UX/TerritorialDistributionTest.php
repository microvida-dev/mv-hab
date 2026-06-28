<?php

namespace Tests\Feature\UX;

use App\Services\Analytics\TerritorialDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class TerritorialDistributionTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_territorial_distribution_uses_aggregated_parish_data(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $dataset = app(TerritorialDistributionService::class)->applicationsByParish([]);

        $this->assertSame('Distribuição territorial', $dataset['title']);
        $this->assertSame('Alcanena', $dataset['items'][0]['label']);
    }
}
