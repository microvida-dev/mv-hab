<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSitemapTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_sitemap_includes_only_public_pages_and_robots_points_to_it(): void
    {
        $program = $this->publicProgram(['slug' => 'programa-sitemap-qa34']);
        $contest = $this->publicContest($program, ['slug' => 'concurso-sitemap-qa34']);
        $housingUnit = $this->publicHousingUnit(['public_slug' => 'fogo-sitemap-qa34']);
        $this->attachToContest($housingUnit, $contest);

        HousingUnit::factory()->create([
            'public_slug' => 'fogo-privado-sitemap-qa34',
            'is_public' => false,
            'public_visibility_status' => PublicVisibilityStatus::Draft->value,
        ]);

        $this->get(route('public.sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee('programa-sitemap-qa34')
            ->assertSee('concurso-sitemap-qa34')
            ->assertSee('fogo-sitemap-qa34')
            ->assertDontSee('fogo-privado-sitemap-qa34')
            ->assertDontSee('/backoffice')
            ->assertDontSee('/area-candidato')
            ->assertDontSee('/download');

        $this->get(route('public.robots'))
            ->assertOk()
            ->assertSee('Sitemap: '.route('public.sitemap'))
            ->assertSee('Disallow: /backoffice');
    }
}
