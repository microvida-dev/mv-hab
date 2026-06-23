<?php

namespace App\Services\Visits;

use App\Enums\VisitSlotStatus;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Support\AuditEvents;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VisitSlotGenerationService
{
    public function __construct(private readonly VisitAuditService $audit) {}

    /**
     * @param  array<string, mixed>  $options
     * @return Collection<int, VisitSlot>
     */
    public function generate(VisitAvailability $availability, User $actor, array $options = []): Collection
    {
        return DB::transaction(function () use ($availability, $actor, $options): Collection {
            $slots = collect();
            $cursor = $availability->starts_at?->copy();
            $end = $availability->ends_at;
            $duration = (int) $availability->slot_duration_minutes;
            $buffer = (int) $availability->buffer_minutes;

            while ($cursor !== null && $end !== null && $cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);

                $slot = VisitSlot::query()
                    ->where('visit_availability_id', $availability->id)
                    ->where('starts_at', $cursor)
                    ->where('ends_at', $slotEnd)
                    ->first();

                if (! $slot instanceof VisitSlot) {
                    $slot = new VisitSlot([
                        'visit_availability_id' => $availability->id,
                        'contest_id' => $availability->contest_id,
                        'housing_unit_id' => $availability->housing_unit_id,
                        'staff_user_id' => $availability->staff_user_id,
                        'starts_at' => $cursor,
                        'ends_at' => $slotEnd,
                        'capacity' => $availability->capacity_per_slot,
                        'location' => $options['location'] ?? null,
                        'meeting_point' => $options['meeting_point'] ?? null,
                        'notes' => $options['notes'] ?? null,
                    ]);
                    $slot->forceFill([
                        'status' => VisitSlotStatus::Available,
                        'booked_count' => 0,
                    ])->save();
                }

                $slots->push($slot->refresh());
                $cursor = $slotEnd->copy()->addMinutes($buffer);
            }

            $this->audit->availability(AuditEvents::UPDATE, $availability, 'Slots de visita gerados.', [
                'slots_generated' => $slots->count(),
                'actor_id' => $actor->id,
            ]);

            return $slots;
        });
    }
}
