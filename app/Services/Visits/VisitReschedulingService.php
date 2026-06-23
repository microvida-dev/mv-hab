<?php

namespace App\Services\Visits;

use App\Enums\InteractionType;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\HousingVisitStatusHistory;
use App\Models\User;
use App\Models\VisitSlot;
use App\Services\CandidateExperience\CandidateInteractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitReschedulingService
{
    public function __construct(
        private readonly VisitBookingService $booking,
        private readonly CandidateInteractionService $interactions,
        private readonly VisitNotificationService $notifications,
        private readonly VisitAuditService $audit,
    ) {}

    public function reschedule(HousingVisit $visit, VisitSlot $newSlot, User $actor, ?string $reason = null): HousingVisit
    {
        return DB::transaction(function () use ($visit, $newSlot, $actor, $reason): HousingVisit {
            $visit = HousingVisit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();

            if (! $visit->isActive()) {
                throw ValidationException::withMessages(['visit' => 'Apenas visitas ativas podem ser reagendadas.']);
            }

            $minimumHours = (int) config('mvhab.candidate_support.minimum_reschedule_hours', 24);
            if ($visit->starts_at !== null && $visit->starts_at->lessThan(now()->addHours($minimumHours))) {
                throw ValidationException::withMessages(['visit' => 'O prazo mínimo para reagendamento já terminou.']);
            }

            $oldSlot = $visit->slot()->lockForUpdate()->first();
            if ($oldSlot instanceof VisitSlot) {
                $this->booking->releaseSlot($oldSlot);
            }

            $lockedNewSlot = VisitSlot::query()->whereKey($newSlot->id)->lockForUpdate()->firstOrFail();
            $this->booking->reserveSlot($lockedNewSlot);

            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));
            $candidate = User::query()->findOrFail($visit->candidate_user_id);

            $visit->forceFill([
                'visit_slot_id' => $lockedNewSlot->id,
                'status' => VisitStatus::Rescheduled,
                'starts_at' => $lockedNewSlot->starts_at,
                'ends_at' => $lockedNewSlot->ends_at,
                'staff_user_id' => $lockedNewSlot->staff_user_id,
                'location' => $lockedNewSlot->location,
                'meeting_point' => $lockedNewSlot->meeting_point,
            ])->save();

            HousingVisitStatusHistory::query()->create([
                'housing_visit_id' => $visit->id,
                'from_status' => $from?->value,
                'to_status' => VisitStatus::Rescheduled->value,
                'changed_by' => $actor->id,
                'reason' => $reason ?: 'Reagendamento',
                'changed_at' => now(),
                'created_at' => now(),
            ]);

            $this->interactions->record(
                user: $candidate,
                type: InteractionType::VisitRescheduled,
                title: 'Visita reagendada',
                description: 'A visita foi reagendada na plataforma.',
                related: $visit,
                application: $visit->application,
                contest: $visit->contest,
                housingUnit: $visit->housingUnit,
                actor: $actor,
            );
            $this->audit->updated($visit, $actor, 'Visita reagendada.');
            $this->notifications->visitRescheduled($visit->refresh(), $actor);

            return $visit->refresh();
        });
    }
}
