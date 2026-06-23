<?php

namespace Database\Factories;

use App\Enums\RentCalculationMethod;
use App\Enums\RentCalculationStatus;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\RentCalculation;
use App\Models\RentRuleSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentCalculation> */
class RentCalculationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rent_rule_set_id' => RentRuleSet::factory(),
            'allocation_id' => Allocation::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'contest_housing_unit_id' => ContestHousingUnit::factory(),
            'status' => RentCalculationStatus::Approved->value,
            'calculation_method' => RentCalculationMethod::EffortRate->value,
            'income_basis' => 'declared_income',
            'income_period' => 'monthly',
            'monthly_household_income' => 1000,
            'annual_household_income' => 12000,
            'monthly_income_per_capita' => 1000,
            'annual_income_per_capita' => 12000,
            'calculated_effort_rate_percentage' => 30,
            'configured_effort_rate_percentage' => 30,
            'base_rent' => 300,
            'minimum_rent' => 50,
            'maximum_rent' => 500,
            'applicable_rent' => 300,
            'deposit_amount' => 300,
            'calculated_at' => now(),
            'approved_at' => now(),
            'snapshot' => ['demo' => true],
        ];
    }
}
