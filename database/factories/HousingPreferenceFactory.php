<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingPreference;
use App\Models\HousingUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HousingPreference> */
class HousingPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'contest_id' => Contest::factory(),
            'contest_housing_unit_id' => ContestHousingUnit::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'preference_order' => 1,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
