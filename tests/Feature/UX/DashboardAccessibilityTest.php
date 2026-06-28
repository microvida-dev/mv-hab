<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardAccessibilityTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_analytics_charts_have_accessible_labels_and_text_alternatives(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $this->actingAs($administrator)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertSee('aria-label="Tendências operacionais"', false)
            ->assertSee('role="img"', false)
            ->assertSee('Alternativa textual do gráfico de barras');
    }
}
