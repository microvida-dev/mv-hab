<?php

namespace Database\Factories;

use App\Models\Contest;
use App\Models\Program;
use App\Models\TypologyAdequacyRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TypologyAdequacyRule> */
class TypologyAdequacyRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'name' => 'Regra de adequação fictícia',
            'is_active' => true,
            'min_household_members' => 1,
            'max_household_members' => 5,
            'min_bedrooms' => 0,
            'max_bedrooms' => 4,
            'priority_order' => 0,
        ];
    }
}
