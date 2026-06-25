<?php

namespace Tests\Feature\PublicPortal;

use App\Models\HousingUnitPublicDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPortalRgpdProtectionTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_public_pages_do_not_expose_personal_data_private_documents_or_internal_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/documents/public.pdf', 'pdf público fictício');

        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'Proteção RGPD QA34',
            'public_slug' => 'protecao-rgpd-qa34',
            'public_summary' => 'Resumo público sem contacto pessoal.',
            'address' => 'Rua reservada RGPD QA34',
            'public_address_visible' => false,
        ]);

        HousingUnitPublicDocument::factory()->for($housingUnit)->create([
            'title' => 'Documento público QA34',
            'path' => 'public-housing/documents/public.pdf',
            'is_public' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('public.housing-units.show', 'protecao-rgpd-qa34'))
            ->assertOk()
            ->assertSee('Documento público QA34')
            ->assertDontSee('Rua reservada RGPD QA34')
            ->assertDontSee('public-housing/documents/public.pdf')
            ->assertDontSee('NIF')
            ->assertDontSee('telefone')
            ->assertDontSee('email')
            ->assertDontSee('audit')
            ->assertDontSee('storage_path');

        $this->getJson(route('public.housing-map.index'))
            ->assertOk()
            ->assertJsonMissing(['address' => 'Rua reservada RGPD QA34'])
            ->assertJsonMissing(['email' => 'cidadao@example.test'])
            ->assertJsonMissing(['storage_path' => 'public-housing/documents/public.pdf']);
    }
}
