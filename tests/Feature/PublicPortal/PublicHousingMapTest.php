<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHousingMapTest extends TestCase
{
    use CreatesAdvancedPublicPortalFixtures;
    use RefreshDatabase;

    public function test_map_returns_only_public_minimal_markers_with_rounded_sensitive_coordinates(): void
    {
        $public = $this->publicHousingUnit([
            'public_title' => 'Mapa QA34 Público',
            'public_slug' => 'mapa-qa34-publico',
            'address' => 'Rua privada não publicável',
            'public_address_visible' => false,
            'public_location_precision' => HousingLocationPrecision::Parish->value,
            'public_latitude' => 39.4595678,
            'public_longitude' => -8.6674567,
        ]);

        HousingUnit::factory()->create([
            'public_title' => 'Mapa QA34 Privado',
            'public_slug' => 'mapa-qa34-privado',
            'is_public' => false,
            'public_visibility_status' => PublicVisibilityStatus::Draft->value,
            'public_latitude' => 39.1,
            'public_longitude' => -8.1,
        ]);

        $response = $this->getJson(route('public.housing-map.index'));

        $response->assertOk()
            ->assertJsonPath('markers.0.reference', $public->public_reference)
            ->assertJsonPath('markers.0.latitude', 39.46)
            ->assertJsonPath('markers.0.longitude', -8.667)
            ->assertJsonMissing(['id' => $public->id])
            ->assertJsonMissing(['address' => 'Rua privada não publicável'])
            ->assertJsonMissing(['storage_path' => 'documents/private.pdf'])
            ->assertJsonMissing(['title' => 'Mapa QA34 Privado']);
    }
}
