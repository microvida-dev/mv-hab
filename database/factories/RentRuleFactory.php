<?php

namespace Database\Factories;

use App\Models\RentRule;
use App\Models\RentRuleSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentRule> */
class RentRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rent_rule_set_id' => RentRuleSet::factory(),
            'name' => 'Escalão fictício',
            'rule_type' => 'income_bracket',
            'operator' => 'between',
            'minimum_value' => 0,
            'maximum_value' => 2000,
            'percentage' => 30,
            'priority_order' => 1,
            'is_active' => true,
        ];
    }
}
