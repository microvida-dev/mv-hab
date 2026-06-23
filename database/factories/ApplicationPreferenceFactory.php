<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationPreference;
use App\Models\HousingUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationPreference>
 */
class ApplicationPreferenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'preference_order' => 1,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
