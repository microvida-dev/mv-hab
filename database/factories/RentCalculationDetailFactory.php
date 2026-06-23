<?php

namespace Database\Factories;

use App\Enums\RentCalculationResult;
use App\Models\RentCalculation;
use App\Models\RentCalculationDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentCalculationDetail> */
class RentCalculationDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rent_calculation_id' => RentCalculation::factory(),
            'code' => 'base_rent',
            'name' => 'Renda base',
            'rule_type' => 'rent',
            'result' => RentCalculationResult::Applied->value,
            'input_value' => 1000,
            'output_value' => 300,
        ];
    }
}
