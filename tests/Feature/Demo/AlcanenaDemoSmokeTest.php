<?php

namespace Tests\Feature\Demo;

use App\Models\HousingUnit;
use Database\Seeders\MunicipalPilotStagingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaDemoSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_demo_routes_render_alcanena_pilot_data(): void
    {
        $this->seed(MunicipalPilotStagingSeeder::class);

        $housingUnit = HousingUnit::query()
            ->where('code', 'ALC-DEMO-T2-MONSANTO')
            ->firstOrFail();

        $this->get(route('public.portal'))->assertOk()->assertSee('Alcanena');
        $this->get(route('public.contests.index'))->assertOk()->assertSee('Concurso n.º 01/2026');
        $this->get(route('public.housing-offer.index'))->assertOk()->assertSee('T2 Monsanto');
        $this->get(route('public.housing-units.show', $housingUnit->public_slug))->assertOk();
        $this->get(route('public.faq'))->assertOk();
    }
}
