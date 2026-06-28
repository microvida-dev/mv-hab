<?php

namespace Tests\Feature\UX;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\UX\Concerns\CreatesAnalyticsFixtures;
use Tests\TestCase;

class ProfileAnalyticsDashboardTest extends TestCase
{
    use CreatesAnalyticsFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
    }

    public function test_municipal_technician_sees_authorized_aggregated_metrics_only(): void
    {
        $technician = $this->analyticsUser('municipal_technician');
        $this->createAnalyticsFixtures($technician);

        $this->actingAs($technician)
            ->get(route('backoffice.analytics.index'))
            ->assertOk()
            ->assertSee('Documentos por validar')
            ->assertSee('Tarefas em atraso')
            ->assertDontSee('NIF');

        if (! $technician->hasPermission('finance.view') && ! $technician->hasPermission('reports.view_financial')) {
            $this->actingAs($technician)
                ->get(route('backoffice.analytics.index'))
                ->assertDontSee('Rendas em aberto');
        }
    }
}
