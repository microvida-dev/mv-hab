<?php

namespace Tests\Feature;

use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\PublicPortal\CreatesAdvancedPublicPortalFixtures;
use Tests\TestCase;

class QA34AdvancedPublicPortalTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_public_portal_supports_advanced_search_map_detail_seo_and_sitemap(): void
    {
        $program = $this->publicProgram(['slug' => 'programa-qa34']);
        $contest = $this->publicContest($program, ['slug' => 'concurso-qa34']);
        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'T2 QA34 Portal Avançado',
            'public_slug' => 't2-qa34-portal-avancado',
            'parish' => 'Alcanena',
            'locality' => 'Minde',
            'energy_rating' => 'A',
            'monthly_rent' => 300,
        ]);
        $this->attachToContest($housingUnit, $contest, accessible: true);

        HousingUnit::factory()->create([
            'public_title' => 'Fogo privado QA34',
            'public_slug' => 'fogo-privado-qa34',
            'public_visibility_status' => PublicVisibilityStatus::Draft->value,
            'is_public' => false,
        ]);

        $this->get(route('public.housing-units.index', [
            'parish' => 'Alcanena',
            'locality' => 'Minde',
            'typology' => 'T2',
            'rent_max' => 350,
            'energy_rating' => 'A',
            'accessible' => 1,
            'program' => 'programa-qa34',
            'contest' => 'concurso-qa34',
        ]))
            ->assertOk()
            ->assertSee('T2 QA34 Portal Avançado')
            ->assertDontSee('Fogo privado QA34')
            ->assertSee('aria-label="Breadcrumb"', false);

        $this->getJson(route('public.housing-map.index', ['parish' => 'Alcanena']))
            ->assertOk()
            ->assertJsonPath('markers.0.title', 'T2 QA34 Portal Avançado')
            ->assertJsonMissing(['id' => $housingUnit->id])
            ->assertJsonMissing(['address' => $housingUnit->address]);

        $this->get(route('public.housing-units.show', 't2-qa34-portal-avancado'))
            ->assertOk()
            ->assertSee('og:title', false)
            ->assertSee('twitter:card', false)
            ->assertSee('RealEstateListing')
            ->assertSee('BreadcrumbList')
            ->assertSee('Simular elegibilidade')
            ->assertDontSee((string) $housingUnit->address);

        $this->get(route('public.sitemap'))
            ->assertOk()
            ->assertSee('t2-qa34-portal-avancado')
            ->assertSee('programa-qa34')
            ->assertSee('concurso-qa34')
            ->assertDontSee('fogo-privado-qa34');
    }
}
