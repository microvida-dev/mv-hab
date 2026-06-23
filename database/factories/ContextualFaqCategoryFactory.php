<?php

namespace Database\Factories;

use App\Models\ContextualFaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContextualFaqCategory>
 */
class ContextualFaqCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word().' '.fake()->unique()->word();

        return [
            'code' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => ucfirst($name),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
