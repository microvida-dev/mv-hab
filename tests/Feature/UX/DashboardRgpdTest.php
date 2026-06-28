<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardRgpdTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_analytics_dashboard_does_not_expose_sensitive_identifiers(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $this->actingAs($administrator)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertDontSee('NIF')
            ->assertDontSee('storage_path')
            ->assertDontSee('/Users/')
            ->assertDontSee('documents/test/documento-teste.pdf');
    }
}
