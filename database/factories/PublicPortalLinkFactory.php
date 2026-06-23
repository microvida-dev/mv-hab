<?php

namespace Database\Factories;

use App\Models\PublicPortalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PublicPortalLink> */
class PublicPortalLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->words(3, true),
            'url' => 'https://www.example.org/'.fake()->slug(),
            'category' => 'institutional',
            'description' => fake()->sentence(),
            'opens_new_tab' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }
}
