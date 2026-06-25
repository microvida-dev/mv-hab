<?php

namespace Tests\Feature\PublicPortal;

use App\Models\HousingUnitImage;
use App\Models\HousingUnitPublicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicHousingDetailSeoTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_detail_contains_seo_gallery_public_documents_and_no_private_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/images/qa34.jpg', 'imagem fictícia');
        Storage::disk('public')->put('public-housing/documents/qa34.pdf', 'pdf fictício');
        Storage::disk('public')->put('public-housing/documents/private.pdf', 'pdf privado fictício');

        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'Ficha SEO QA34',
            'public_slug' => 'ficha-seo-qa34',
            'seo_title' => 'SEO Habitação QA34',
            'seo_description' => 'Descrição SEO pública QA34.',
            'address' => 'Rua reservada QA34',
            'public_address_visible' => false,
        ]);

        HousingUnitImage::factory()->cover()->for($housingUnit)->create([
            'path' => 'public-housing/images/qa34.jpg',
            'alt_text' => 'Imagem pública QA34',
            'is_public' => true,
        ]);
        HousingUnitPublicDocument::factory()->for($housingUnit)->create([
            'title' => 'Brochura pública QA34',
            'path' => 'public-housing/documents/qa34.pdf',
            'is_public' => true,
            'published_at' => now()->subMinute(),
        ]);
        $privateDocument = HousingUnitPublicDocument::factory()->private()->for($housingUnit)->create([
            'title' => 'Documento privado QA34',
            'path' => 'public-housing/documents/private.pdf',
        ]);

        $this->get(route('public.housing-units.show', 'ficha-seo-qa34'))
            ->assertOk()
            ->assertSee('SEO Habitação QA34')
            ->assertSee('meta name="description"', false)
            ->assertSee('og:image', false)
            ->assertSee('twitter:card', false)
            ->assertSee('RealEstateListing')
            ->assertSee('BreadcrumbList')
            ->assertSee('Imagem pública QA34')
            ->assertSee('Brochura pública QA34')
            ->assertDontSee('Documento privado QA34')
            ->assertDontSee('public-housing/documents/private.pdf')
            ->assertDontSee('Rua reservada QA34');

        $this->get(route('public.housing-documents.download', $privateDocument))
            ->assertNotFound();
    }
}
