<?php

namespace Database\Factories;

use App\Enums\VisitSlotStatus;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisitSlot>
 */
class VisitSlotFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+2 days', '+20 days');

        return [
            'visit_availability_id' => VisitAvailability::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+30 minutes'),
            'status' => VisitSlotStatus::Available->value,
            'capacity' => 2,
            'booked_count' => 0,
            'location' => 'Edifício municipal',
            'meeting_point' => 'Entrada principal',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (VisitSlot $slot): void {
            $slot->loadMissing('availability');

            $availability = $slot->availability;

            if (! $availability instanceof VisitAvailability) {
                return;
            }

            $slot->forceFill([
                'municipality_id' => $availability->municipality_id,
                'contest_id' => $slot->contest_id
                    ?? $availability->contest_id,
                'housing_unit_id' => $slot->housing_unit_id
                    ?? $availability->housing_unit_id,
                'staff_user_id' => $slot->staff_user_id
                    ?? $availability->staff_user_id,
            ])->saveQuietly();
        });
    }
}
