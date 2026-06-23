<?php

namespace Database\Factories;

use App\Models\HousingUnit;
use App\Models\HousingUnitImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HousingUnitImage> */
class HousingUnitImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'housing_unit_id' => HousingUnit::factory(),
            'uploaded_by' => null,
            'approved_by' => null,
            'title' => 'Imagem demonstrativa',
            'alt_text' => 'Imagem institucional da habitação',
            'disk' => 'public',
            'path' => 'public-housing/images/demo.jpg',
            'thumbnail_path' => null,
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 1200,
            'height' => 800,
            'is_cover' => false,
            'is_public' => true,
            'approved_at' => now()->subHour(),
            'sort_order' => 0,
        ];
    }

    public function cover(): static
    {
        return $this->state(fn () => [
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }
}
