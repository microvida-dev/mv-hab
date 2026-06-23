<?php

namespace Database\Factories;

use App\Enums\DashboardType;
use App\Models\DashboardDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardDefinition>
 */
class DashboardDefinitionFactory extends Factory
{
    protected $model = DashboardDefinition::class;

    public function definition(): array
    {
        return ['code' => fake()->unique()->slug(2), 'name' => fake()->sentence(2), 'dashboard_type' => DashboardType::Operational, 'is_active' => true, 'is_default' => false];
    }
}
