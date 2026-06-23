<?php

namespace Database\Factories;

use App\Models\AdhesionRegistration;
use App\Models\Citizen;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'citizen_id' => Citizen::factory(),
            'adhesion_registration_id' => null,
            'name' => 'Agregado '.fake()->lastName(),
            'household_type' => 'family',
            'monthly_income' => fake()->randomFloat(2, 450, 2500),
            'members_count' => fake()->numberBetween(1, 6),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function candidate(?AdhesionRegistration $registration = null): static
    {
        return $this->state(fn () => [
            'citizen_id' => null,
            'adhesion_registration_id' => $registration->id ?? AdhesionRegistration::factory(),
            'monthly_income' => 0,
            'members_count' => 0,
        ]);
    }
}
