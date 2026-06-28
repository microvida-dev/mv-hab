<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_administrator_sees_municipal_analytics_center(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $this->actingAs($administrator)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertSee('Centro Analítico Municipal')
            ->assertSee('Analytics executivos')
            ->assertSee('KPIs executivos')
            ->assertSee('Candidaturas recebidas')
            ->assertSee('Funil operacional municipal')
            ->assertDontSee('storage_path');
    }
}
