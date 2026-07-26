<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use App\Models\VisitSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousingVisit>
 */
class HousingVisitFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+2 days', '+20 days');

        return [
            'visit_number' => 'VIS-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'visit_slot_id' => VisitSlot::factory(),
            'candidate_user_id' => User::factory(),
            'status' => VisitStatus::PendingConfirmation->value,
            'scheduled_at' => now(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+30 minutes'),
            'location' => 'Edifício municipal',
            'meeting_point' => 'Entrada principal',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (HousingVisit $visit): void {
            $visit->loadMissing('slot');

            $slot = $visit->slot;

            if (! $slot instanceof VisitSlot) {
                return;
            }

            $visit->forceFill([
                'municipality_id' => $slot->municipality_id,
                'contest_id' => $visit->contest_id
                    ?? $slot->contest_id,
                'housing_unit_id' => $visit->housing_unit_id
                    ?? $slot->housing_unit_id,
                'staff_user_id' => $visit->staff_user_id
                    ?? $slot->staff_user_id,
            ])->saveQuietly();
        });
    }
}
