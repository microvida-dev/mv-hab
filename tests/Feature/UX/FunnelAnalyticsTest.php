<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class FunnelAnalyticsTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_funnel_is_visual_and_does_not_change_processes(): void
    {
        $administrator = $this->analyticsUser();
        $this->createAnalyticsFixtures($administrator);

        $this->actingAs($administrator)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertSee('Simulação')
            ->assertSee('Candidatura')
            ->assertSee('Contrato')
            ->assertDontSee('Aprovar automaticamente');
    }
}
