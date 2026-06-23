<?php

namespace Database\Factories;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\PublicVisibilityStatus;
use App\Models\HousingUnit;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HousingUnit>
 */
class HousingUnitFactory extends Factory
{
    public function definition(): array
    {
        $code = 'HU-'.fake()->unique()->numerify('###');

        return [
            'municipality_id' => null,
            'code' => $code,
            'address' => fake()->streetAddress(),
            'typology' => fake()->randomElement(['T1', 'T2', 'T3', 'T4']),
            'bedrooms' => fake()->numberBetween(0, 4),
            'monthly_rent' => fake()->randomFloat(2, 125, 650),
            'status' => fake()->randomElement(HousingUnitStatus::values()),
            'public_reference' => null,
            'public_title' => null,
            'public_slug' => null,
            'public_summary' => null,
            'public_description' => null,
            'parish' => null,
            'locality' => null,
            'postal_code' => null,
            'floor' => null,
            'gross_area_sqm' => null,
            'usable_area_sqm' => null,
            'energy_rating' => null,
            'public_location_description' => null,
            'public_address_visible' => false,
            'public_latitude' => null,
            'public_longitude' => null,
            'public_location_precision' => HousingLocationPrecision::Parish->value,
            'public_status' => HousingPublicStatus::Available->value,
            'public_visibility_status' => PublicVisibilityStatus::Draft->value,
            'is_public' => false,
            'published_at' => null,
            'unpublished_at' => null,
            'public_sort_order' => 0,
            'seo_title' => null,
            'seo_description' => null,
            'og_image_path' => null,
        ];
    }

    public function publiclyVisible(): static
    {
        return $this->state(function (array $attributes) {
            $title = ($attributes['typology'] ?? 'Habitação').' municipal '.fake()->unique()->numerify('###');

            return [
                'municipality_id' => Municipality::factory(),
                'public_reference' => fake()->unique()->bothify('PUB-####'),
                'public_title' => $title,
                'public_slug' => Str::slug($title),
                'public_summary' => 'Habitação municipal de demonstração disponível para consulta pública.',
                'public_description' => 'Ficha pública fictícia para validação do portal de oferta habitacional.',
                'parish' => fake()->randomElement(['Alcanena', 'Minde', 'Moitas Venda']),
                'locality' => fake()->randomElement(['Alcanena', 'Minde', 'Vila Moreira']),
                'postal_code' => '0000-000',
                'floor' => fake()->randomElement(['R/C', '1.º', '2.º']),
                'gross_area_sqm' => fake()->randomFloat(2, 45, 120),
                'usable_area_sqm' => fake()->randomFloat(2, 40, 105),
                'energy_rating' => fake()->randomElement(['A', 'B', 'C']),
                'public_location_description' => 'Freguesia de Alcanena',
                'public_latitude' => 39.4595000 + fake()->randomFloat(7, -0.01, 0.01),
                'public_longitude' => -8.6674000 + fake()->randomFloat(7, -0.01, 0.01),
                'public_location_precision' => HousingLocationPrecision::Parish->value,
                'public_status' => HousingPublicStatus::Available->value,
                'public_visibility_status' => PublicVisibilityStatus::Published->value,
                'is_public' => true,
                'published_at' => now()->subHour(),
                'unpublished_at' => null,
                'seo_title' => $title.' · MV HAB',
                'seo_description' => 'Oferta habitacional municipal fictícia para consulta pública.',
            ];
        });
    }
}
