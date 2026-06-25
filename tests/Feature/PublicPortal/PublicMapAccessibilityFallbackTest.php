<?php

namespace Tests\Feature\PublicPortal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMapAccessibilityFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_offer_map_has_textual_fallback_when_no_public_coordinates_exist(): void
    {
        $this->get(route('public.housing-offer.index'))
            ->assertOk()
            ->assertSee('Mapa da oferta')
            ->assertSee('O mapa será apresentado quando existirem habitações publicadas com coordenadas públicas.');
    }
}
