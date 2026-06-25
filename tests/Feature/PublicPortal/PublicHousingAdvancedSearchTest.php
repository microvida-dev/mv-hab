<?php

namespace Tests\Feature\PublicPortal;

use App\Models\VisitSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHousingAdvancedSearchTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_advanced_filters_are_combinable_and_preserve_query_string(): void
    {
        $contest = $this->publicContest(null, ['slug' => 'concurso-pesquisa-qa34']);
        $matching = $this->publicHousingUnit([
            'public_title' => 'T1 Pesquisa QA34',
            'public_slug' => 't1-pesquisa-qa34',
            'typology' => 'T1',
            'parish' => 'Alcanena',
            'locality' => 'Vila Moreira',
            'public_location_description' => 'Zona Norte',
            'monthly_rent' => 250,
            'energy_rating' => 'A',
        ]);
        $other = $this->publicHousingUnit([
            'public_title' => 'T3 Fora QA34',
            'public_slug' => 't3-fora-qa34',
            'typology' => 'T3',
            'parish' => 'Minde',
            'locality' => 'Minde',
            'monthly_rent' => 650,
            'energy_rating' => 'C',
        ]);

        $this->attachToContest($matching, $contest, accessible: true);
        $this->attachToContest($other, $contest);
        VisitSlot::factory()->for($matching)->for($contest)->create();

        $response = $this->get(route('public.housing-units.index', [
            'q' => 'Pesquisa',
            'typology' => 'T1',
            'parish' => 'Alcanena',
            'locality' => 'Vila Moreira',
            'zone' => 'Norte',
            'rent_max' => 300,
            'energy_rating' => 'A',
            'accessible' => 1,
            'visit_available' => 1,
        ]));

        $response->assertOk()
            ->assertSee('T1 Pesquisa QA34')
            ->assertDontSee('T3 Fora QA34')
            ->assertSee('value="Vila Moreira" selected', false)
            ->assertSee('name="visit_available" value="1" checked', false);
    }

    public function test_empty_search_returns_controlled_empty_state(): void
    {
        $this->get(route('public.housing-units.index', ['q' => 'sem-resultados-qa34']))
            ->assertOk()
            ->assertSee('Não existem habitações públicas com estes filtros.');
    }
}
