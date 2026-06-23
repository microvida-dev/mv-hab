<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\ContestStatus;
use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\PublicVisibilityStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\HousingUnitPublicDocument;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicHousingOfferSprint20Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_offer_and_filter_housing_units(): void
    {
        $contest = $this->openContest();
        $visibleUnit = $this->publicHousingUnit([
            'public_title' => 'T2 público em Alcanena',
            'public_slug' => 't2-publico-alcanena',
            'typology' => 'T2',
            'parish' => 'Alcanena',
        ]);
        $hiddenUnit = HousingUnit::factory()->create([
            'public_title' => 'Habitação escondida',
            'public_slug' => 'habitacao-escondida',
            'is_public' => false,
            'public_visibility_status' => PublicVisibilityStatus::Draft->value,
        ]);

        ContestHousingUnit::factory()->for($contest)->for($visibleUnit)->create();
        ContestHousingUnit::factory()->for($contest)->for($hiddenUnit)->create();

        $this->get(route('public.housing-offer.index'))
            ->assertOk()
            ->assertSee('Oferta Habitacional')
            ->assertSee('T2 público em Alcanena')
            ->assertDontSee('Habitação escondida');

        $this->get(route('public.housing-offer.index', ['typology' => 'T3']))
            ->assertOk()
            ->assertDontSee('T2 público em Alcanena');
    }

    public function test_public_contest_detail_lists_public_housing_units(): void
    {
        $contest = $this->openContest(['title' => 'Concurso com imóveis públicos']);
        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'T1 associado ao concurso',
            'public_slug' => 't1-associado-ao-concurso',
        ]);

        ContestHousingUnit::factory()->for($contest)->for($housingUnit)->create();

        $this->get(route('public.contests.show', $contest->slug))
            ->assertOk()
            ->assertSee('Concurso com imóveis públicos')
            ->assertSee('Habitações deste concurso')
            ->assertSee('T1 associado ao concurso');
    }

    public function test_public_map_endpoint_returns_public_markers_without_internal_paths_or_addresses(): void
    {
        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'Marcador público',
            'public_slug' => 'marcador-publico',
            'address' => 'Rua interna 123',
            'public_address_visible' => false,
            'public_latitude' => 39.4595000,
            'public_longitude' => -8.6674000,
        ]);

        $response = $this->getJson(route('public.housing-map.index'));

        $response->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonPath('markers.0.title', 'Marcador público')
            ->assertJsonMissing(['address' => 'Rua interna 123'])
            ->assertJsonMissing(['path' => 'public-housing/images/private.jpg']);

        $this->assertSame($housingUnit->public_slug, basename($response->json('markers.0.url')));
    }

    public function test_public_housing_unit_detail_exposes_public_documents_without_storage_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/documents/ficha.pdf', 'PDF público fictício');

        $housingUnit = $this->publicHousingUnit([
            'public_title' => 'Habitação com documento',
            'public_slug' => 'habitacao-com-documento',
        ]);
        $document = HousingUnitPublicDocument::factory()->for($housingUnit)->create([
            'title' => 'Ficha técnica pública',
            'path' => 'public-housing/documents/ficha.pdf',
            'is_public' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get(route('public.housing-units.show', $housingUnit->public_slug))
            ->assertOk()
            ->assertSee('Habitação com documento')
            ->assertSee('Ficha técnica pública')
            ->assertDontSee('public-housing/documents/ficha.pdf');

        $this->get(route('public.housing-documents.download', $document))
            ->assertOk();

        $this->assertSame(1, $document->fresh()->download_count);
    }

    public function test_private_public_document_cannot_be_downloaded(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-housing/documents/privado.pdf', 'PDF privado fictício');

        $document = HousingUnitPublicDocument::factory()
            ->private()
            ->for($this->publicHousingUnit())
            ->create(['path' => 'public-housing/documents/privado.pdf']);

        $this->get(route('public.housing-documents.download', $document))
            ->assertNotFound();
    }

    public function test_backoffice_can_update_public_profile_and_candidate_is_blocked(): void
    {
        $this->seed(SystemAccessSeeder::class);

        $housingUnit = HousingUnit::factory()->create([
            'code' => 'HU-PUBLIC-20',
            'typology' => 'T2',
            'monthly_rent' => 325,
            'status' => 'available',
        ]);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('backoffice.public-portal.housing-units.edit', $housingUnit))
            ->assertForbidden();

        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');

        $this->actingAs($administrator)
            ->put(route('backoffice.public-portal.housing-units.update', $housingUnit), [
                'public_reference' => 'PUB-20',
                'public_title' => 'Ficha pública editada',
                'public_slug' => 'ficha-publica-editada',
                'public_summary' => 'Resumo público de teste.',
                'public_description' => 'Descrição pública de teste.',
                'parish' => 'Alcanena',
                'locality' => 'Alcanena',
                'public_location_description' => 'Freguesia de Alcanena',
                'public_latitude' => 39.4595,
                'public_longitude' => -8.6674,
                'public_location_precision' => HousingLocationPrecision::Parish->value,
                'public_status' => HousingPublicStatus::Available->value,
                'public_visibility_status' => PublicVisibilityStatus::Published->value,
                'public_sort_order' => 1,
                'is_public' => '1',
            ])
            ->assertRedirect(route('backoffice.public-portal.housing-units.edit', $housingUnit));

        $this->assertDatabaseHas('housing_units', [
            'id' => $housingUnit->id,
            'public_slug' => 'ficha-publica-editada',
            'is_public' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publicHousingUnit(array $overrides = []): HousingUnit
    {
        return HousingUnit::factory()->publiclyVisible()->create(array_merge([
            'public_title' => 'Habitação pública de teste',
            'public_slug' => 'habitacao-publica-de-teste-'.fake()->unique()->numerify('###'),
            'public_summary' => 'Resumo público sem dados pessoais.',
            'parish' => 'Alcanena',
            'public_location_description' => 'Freguesia de Alcanena',
            'public_latitude' => 39.4595000,
            'public_longitude' => -8.6674000,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function openContest(array $overrides = []): Contest
    {
        return Contest::factory()
            ->for(Program::factory()->published())
            ->create(array_merge([
                'status' => ContestStatus::Published->value,
                'published_at' => now()->subDay(),
                'opens_at' => now()->subDay(),
                'closes_at' => now()->addMonth(),
            ], $overrides));
    }
}
