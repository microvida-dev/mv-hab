<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Citizen;
use App\Models\Contract;
use App\Models\HousingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    public function definition(): array
    {
        return [
            'citizen_id' => Citizen::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'start_date' => fake()->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
            'end_date' => null,
            'monthly_rent' => fake()->randomFloat(2, 125, 650),
            'status' => fake()->randomElement(ContractStatus::values()),
        ];
    }
}
