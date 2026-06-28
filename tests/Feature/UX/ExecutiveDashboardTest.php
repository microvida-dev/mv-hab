<?php

namespace Tests\Feature\UX;

use Database\Seeders\DashboardDefinitionSeeder;
use Database\Seeders\DashboardWidgetSeeder;
use Database\Seeders\IndicatorDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        $this->seed([
            IndicatorDefinitionSeeder::class,
            DashboardDefinitionSeeder::class,
            DashboardWidgetSeeder::class,
        ]);
    }

    public function test_executive_dashboard_includes_accessible_analytics_blocks(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $this->actingAs($administrator)
            ->get(route('backoffice.reports.executive'))
            ->assertOk()
            ->assertSee('Painel executivo')
            ->assertSee('Leitura executiva municipal')
            ->assertSee('Alternativa textual do funil municipal')
            ->assertSee('Centro analítico');
    }
}
