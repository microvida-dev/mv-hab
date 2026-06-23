<?php

namespace Database\Factories;

use App\Enums\RentCalculationMethod;
use App\Enums\RentRuleSetStatus;
use App\Models\Program;
use App\Models\RentRuleSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentRuleSet> */
class RentRuleSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => null,
            'name' => 'Regra demo de renda',
            'description' => 'Regra fictícia para testes automatizados.',
            'status' => RentRuleSetStatus::Active->value,
            'calculation_method' => RentCalculationMethod::EffortRate->value,
            'income_period' => 'monthly',
            'income_basis' => 'declared_income',
            'effort_rate_percentage' => 30,
            'minimum_rent' => 50,
            'maximum_rent' => 500,
            'deposit_months' => 1,
            'rounding_mode' => 'nearest',
            'rounding_precision' => 2,
            'requires_manual_approval' => true,
            'allow_manual_override' => true,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
