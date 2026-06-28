<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\FunnelAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class FunnelAnalysisServiceTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    public function test_funnel_service_returns_expected_municipal_stages(): void
    {
        $this->seedAccess();
        $this->createAnalyticsFixtures($this->analyticsUser());

        $steps = app(FunnelAnalysisService::class)->municipalFlow([]);
        $labels = collect($steps)->pluck('label')->all();

        $this->assertContains('Candidatura', $labels);
        $this->assertContains('Contrato', $labels);
    }
}
