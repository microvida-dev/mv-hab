<?php

namespace Database\Factories;

use App\Models\HousingUnit;
use App\Models\HousingUnitFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HousingUnitFeature> */
class HousingUnitFeatureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'housing_unit_id' => HousingUnit::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => fake()->randomElement(['Área útil', 'Piso', 'Eficiência energética', 'Acessibilidade']),
            'value' => fake()->randomElement(['62 m2', '2.º piso', 'Classe B', 'Sem barreiras no acesso comum']),
            'icon' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_public' => true,
        ];
    }
}
