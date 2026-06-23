<?php

namespace Database\Factories;

use App\Enums\ContestHousingUnitStatus;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContestHousingUnit> */
class ContestHousingUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'status' => ContestHousingUnitStatus::Available->value,
            'typology' => fake()->randomElement(['T1', 'T2', 'T3']),
            'bedrooms' => fake()->numberBetween(1, 3),
            'min_occupants' => 1,
            'max_occupants' => fake()->numberBetween(2, 5),
            'accessible' => false,
            'monthly_rent' => fake()->randomFloat(2, 150, 500),
        ];
    }
}
