<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_candidate_cannot_access_internal_analytics_dashboard(): void
    {
        $candidate = $this->analyticsUser('candidate');

        $this->actingAs($candidate)
            ->get(route('backoffice.analytics.index'))
            ->assertForbidden();
    }

    public function test_auditor_gets_read_only_analytics_without_mutation_actions(): void
    {
        $auditor = $this->analyticsUser('auditor');

        $this->actingAs($auditor)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertSee('Centro Analítico Municipal')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Aprovar');
    }
}
