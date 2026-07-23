<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'name' => fake()->name(),
            'document_number' => fake()->unique()->bothify('CC########'),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetAddress(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
