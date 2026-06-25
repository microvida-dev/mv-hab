<?php

namespace Tests\Unit\PublicPortal;

use App\Models\HousingUnit;
use App\Models\HousingUnitImage;
use App\Services\PublicPortal\PublicPortalSeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicSeoMetadataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_housing_unit_metadata_contains_schema_breadcrumbs_and_public_image_only(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/images/seo.jpg', 'imagem fictícia');

        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'public_title' => 'SEO Service QA34',
            'public_slug' => 'seo-service-qa34',
            'seo_title' => 'Título SEO QA34',
            'seo_description' => 'Descrição SEO QA34.',
            'og_image_path' => 'private/not-used.jpg',
        ]);
        HousingUnitImage::factory()->cover()->for($housingUnit)->create([
            'path' => 'public-housing/images/seo.jpg',
            'is_public' => true,
        ]);

        $service = app(PublicPortalSeoService::class);
        $metadata = $service->housingUnit($housingUnit->load(['coverImage', 'publicImages']));
        $jsonLd = $service->housingUnitJsonLd($housingUnit);

        $this->assertSame('Título SEO QA34', $metadata['title']);
        $this->assertStringContainsString('public-housing/images/seo.jpg', (string) $metadata['og_image']);
        $this->assertSame('article', $metadata['og_type']);
        $this->assertSame('https://schema.org', $jsonLd['@context']);
        $this->assertSame('RealEstateListing', $jsonLd['@graph'][0]['@type']);
        $this->assertSame('BreadcrumbList', $jsonLd['@graph'][1]['@type']);
        $this->assertStringNotContainsString('private/not-used.jpg', json_encode($jsonLd) ?: '');
    }
}
