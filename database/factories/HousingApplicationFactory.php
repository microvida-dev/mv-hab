<?php

namespace Database\Factories;

use App\Enums\HousingApplicationStatus;
use App\Models\Citizen;
use App\Models\HousingApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousingApplication>
 */
class HousingApplicationFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(HousingApplicationStatus::values());

        return [
            'citizen_id' => Citizen::factory(),
            'household_id' => null,
            'status' => $status,
            'priority_score' => fake()->numberBetween(0, 100),
            'notes' => fake()->optional()->sentence(),
            'submitted_at' => $status === HousingApplicationStatus::Draft->value
                ? null
                : fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
