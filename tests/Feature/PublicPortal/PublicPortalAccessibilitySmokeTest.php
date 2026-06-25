<?php

namespace Tests\Feature\PublicPortal;

use App\Models\HousingUnitImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPortalAccessibilitySmokeTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_public_housing_pages_have_accessible_filters_images_breadcrumbs_and_map_fallback(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/images/accessibility.jpg', 'imagem fictícia');

        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'Acessibilidade QA34',
            'public_slug' => 'acessibilidade-qa34',
        ]);
        HousingUnitImage::factory()->cover()->for($housingUnit)->create([
            'path' => 'public-housing/images/accessibility.jpg',
            'alt_text' => 'Fachada pública acessível QA34',
        ]);

        $this->get(route('public.housing-units.index'))
            ->assertOk()
            ->assertSee('<label', false)
            ->assertSee('Pesquisar')
            ->assertSee('Tipologia')
            ->assertSee('Freguesia')
            ->assertSee('aria-label="Breadcrumb"', false);

        $this->get(route('public.housing-offer.index'))
            ->assertOk()
            ->assertSee('Mapa da oferta')
            ->assertSee('localizações públicas')
            ->assertSee('aria-label="Navegação pública"', false);

        $this->get(route('public.housing-units.show', 'acessibilidade-qa34'))
            ->assertOk()
            ->assertSee('alt="Fachada pública acessível QA34"', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }
}
