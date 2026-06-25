<?php

namespace Tests\Feature\Operations;

use App\Enums\ContestHousingUnitStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MunicipalSmokeReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_public_smoke_routes_load_and_private_areas_require_login(): void
    {
        $program = Program::factory()->published()->create([
            'name' => 'Programa Municipal QA36',
            'slug' => 'programa-municipal-qa36',
        ]);

        $contest = Contest::factory()
            ->for($program)
            ->open()
            ->create([
                'title' => 'Concurso Municipal QA36',
                'slug' => 'concurso-municipal-qa36',
            ]);

        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'public_title' => 'Fogo Municipal QA36',
            'public_slug' => 'fogo-municipal-qa36',
            'public_address_visible' => false,
            'parish' => 'Alcanena',
            'public_latitude' => 39.4595000,
            'public_longitude' => -8.6674000,
        ]);

        ContestHousingUnit::factory()
            ->for($program)
            ->for($contest)
            ->for($housingUnit)
            ->create([
                'status' => ContestHousingUnitStatus::Available->value,
            ]);

        $this->get(route('public.portal'))->assertOk()->assertSee('Concurso Municipal QA36');
        $this->get(route('public.contests.index'))->assertOk()->assertSee('Concurso Municipal QA36');
        $this->get(route('public.housing-offer.index'))->assertOk()->assertSee('Fogo Municipal QA36');
        $this->get(route('public.housing-units.show', $housingUnit->public_slug))->assertOk();
        $this->get(route('public.faq'))->assertOk();

        foreach ([
            'dashboard',
            'candidate.dashboard',
            'tenant.dashboard',
            'backoffice.housing-visits.index',
            'backoffice.support-tickets.index',
            'backoffice.work-tasks.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "A rota {$routeName} deve existir.");
            $this->get(route($routeName))->assertRedirect(route('login'));
        }
    }

    public function test_operational_routes_for_visits_tickets_faq_and_work_tasks_exist(): void
    {
        foreach ([
            'candidate.visits.index',
            'candidate.support-tickets.index',
            'candidate.contextual-faq.index',
            'backoffice.housing-visits.index',
            'backoffice.support-tickets.index',
            'backoffice.contextual-faqs.index',
            'backoffice.work-tasks.index',
            'backoffice.work-tasks.dashboard',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "A rota {$routeName} deve existir.");
        }
    }
}
