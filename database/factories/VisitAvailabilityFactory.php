<?php

namespace Database\Factories;

use App\Models\Contest;
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
            'contest_id' => Contest::factory(),
            'housing_unit_id' => null,
            'staff_user_id' => null,
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

    public function configure(): static
    {
        return $this->afterCreating(
            function (VisitAvailability $availability): void {
                $availability->loadMissing([
                    'contest.program',
                    'housingUnit',
                ]);

                $municipalityIds = collect([
                    $availability->contest?->program?->municipality_id,
                    $availability->housingUnit?->municipality_id,
                ])->filter()->unique()->values();

                if ($municipalityIds->count() === 1) {
                    $availability->forceFill([
                        'municipality_id' => (int) $municipalityIds->first(),
                    ])->saveQuietly();
                }
            },
        );
    }
}
