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
}
