<?php

namespace Tests\Unit\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Models\HousingUnit;
use App\Services\PublicPortal\PublicMapPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMapPayloadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_marker_payload_is_minimal_and_rounds_non_exact_coordinates(): void
    {
        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'public_reference' => 'PUB-QA34',
            'public_title' => 'Payload Mapa QA34',
            'public_slug' => 'payload-mapa-qa34',
            'address' => 'Rua privada não exposta',
            'public_location_precision' => HousingLocationPrecision::Parish->value,
            'public_latitude' => 39.4595678,
            'public_longitude' => -8.6674567,
        ]);

        $payload = app(PublicMapPayloadService::class)->marker($housingUnit);

        $this->assertSame('PUB-QA34', $payload['reference']);
        $this->assertSame(39.46, $payload['latitude']);
        $this->assertSame(-8.667, $payload['longitude']);
        $this->assertArrayNotHasKey('id', $payload);
        $this->assertArrayNotHasKey('address', $payload);
        $this->assertArrayNotHasKey('storage_path', $payload);
    }
}
