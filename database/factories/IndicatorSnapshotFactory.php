<?php

namespace Database\Factories;

use App\Models\IndicatorDefinition;
use App\Models\IndicatorSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndicatorSnapshot>
 */
class IndicatorSnapshotFactory extends Factory
{
    protected $model = IndicatorSnapshot::class;

    public function definition(): array
    {
        return ['indicator_definition_id' => IndicatorDefinition::factory(), 'value_numeric' => fake()->numberBetween(0, 100), 'filters' => [], 'filters_hash' => hash('sha256', '{}'), 'status' => 'available', 'calculated_at' => now(), 'calculated_by' => User::factory()];
    }
}
