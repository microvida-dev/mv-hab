<?php

namespace Database\Factories;

use App\Models\IncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeSource>
 */
class IncomeSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
