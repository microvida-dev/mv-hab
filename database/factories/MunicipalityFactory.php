<?php

namespace Database\Factories;

use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipality>
 */
class MunicipalityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Município '.fake()->unique()->city(),
            'code' => fake()->unique()->bothify('MUN-###'),
            'tax_number' => null,
            'contact_email' => fake()->unique()->safeEmail(),
            'settings' => [],
            'active' => true,
        ];
    }
}
