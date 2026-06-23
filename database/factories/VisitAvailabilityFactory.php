<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisitAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitAvailability>
 */
class VisitAvailabilityFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+2 days', '+20 days');

        return [
            'staff_user_id' => User::factory(),
            'title' => 'Disponibilidade de visita '.fake()->numerify('###'),
            'description' => fake()->optional()->sentence(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+3 hours'),
            'slot_duration_minutes' => 30,
            'capacity_per_slot' => 2,
            'buffer_minutes' => 0,
            'timezone' => config('app.timezone', 'UTC'),
            'is_active' => true,
        ];
    }
}
