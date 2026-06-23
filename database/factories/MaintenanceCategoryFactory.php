<?php

namespace Database\Factories;

use App\Enums\MaintenanceUrgency;
use App\Models\MaintenanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceCategory>
 */
class MaintenanceCategoryFactory extends Factory
{
    protected $model = MaintenanceCategory::class;

    public function definition(): array
    {
        return [
            'code' => 'CAT-'.fake()->unique()->bothify('??##'),
            'name' => fake()->randomElement(['Canalização', 'Eletricidade', 'Carpintaria', 'Equipamentos']),
            'description' => fake()->sentence(),
            'default_urgency' => fake()->randomElement(MaintenanceUrgency::values()),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
