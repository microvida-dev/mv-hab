<?php

namespace App\Services\Visits;

use App\Enums\InteractionType;
use App\Enums\VisitCancellationReason;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\HousingVisitStatusHistory;
use App\Models\User;
use App\Models\VisitSlot;
use App\Services\CandidateExperience\CandidateInteractionService;
use App\Services\Municipalities\VisitMunicipalContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitCancellationService
{
    public function __construct(
        private readonly VisitBookingService $booking,
        private readonly CandidateInteractionService $interactions,
        private readonly VisitNotificationService $notifications,
        private readonly VisitAuditService $audit,
        private readonly VisitMunicipalContextService $municipalContext,
    ) {}

    public function cancel(HousingVisit $visit, User $actor, VisitCancellationReason $reason, ?string $notes = null): HousingVisit
    {
        return DB::transaction(function () use ($visit, $actor, $reason, $notes): HousingVisit {
            $visit = HousingVisit::query()->whereKey($visit->id)->lockForUpdate()->firstOrFail();
            $candidateActor = $visit->belongsToCandidate($actor)
                && $actor->hasPermission('visits.update');

            if ($candidateActor) {
                $this->municipalContext->validateCandidateVisit(
                    $visit,
                    $actor,
                );
            } elseif ($actor->hasPermission('visits.cancel')) {
                $this->municipalContext->validateVisitForActor(
                    $visit,
                    $actor,
                );
            } else {
                throw ValidationException::withMessages([
                    'visit' => 'O utilizador não pode cancelar esta visita.',
                ]);
            }

            if (! $visit->isActive()) {
                throw ValidationException::withMessages(['visit' => 'A visita não pode ser cancelada neste estado.']);
            }

            if ($candidateActor) {
                $minimumHours = (int) config('mvhab.candidate_support.minimum_cancel_hours', 24);
                if ($visit->starts_at !== null && $visit->starts_at->lessThan(now()->addHours($minimumHours))) {
                    throw ValidationException::withMessages(['visit' => 'O prazo mínimo para cancelamento já terminou.']);
                }
            }

            $slot = $visit->slot()->lockForUpdate()->first();
            if ($slot instanceof VisitSlot) {
                $this->booking->releaseSlot($slot);
            }

            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));
            $candidate = User::query()->findOrFail($visit->candidate_user_id);
            $to = $candidateActor
                ? VisitStatus::CancelledByCandidate
                : VisitStatus::CancelledByStaff;
            $visit->forceFill([
                'status' => $to,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
                'cancellation_notes' => $notes,
            ])->save();

            HousingVisitStatusHistory::query()->create([
                'housing_visit_id' => $visit->id,
                'from_status' => $from?->value,
                'to_status' => $to->value,
                'changed_by' => $actor->id,
                'reason' => $reason->value,
                'notes' => $notes,
                'changed_at' => now(),
                'created_at' => now(),
            ]);

            $this->interactions->record(
                user: $candidate,
                type: InteractionType::VisitCancelled,
                title: 'Visita cancelada',
                description: 'A visita foi cancelada na plataforma.',
                related: $visit,
                application: $visit->application,
                contest: $visit->contest,
                housingUnit: $visit->housingUnit,
                actor: $actor,
            );
            $this->audit->updated($visit, $actor, 'Visita cancelada.');
            $this->notifications->visitCancelled($visit->refresh(), $actor);

            return $visit->refresh();
        });
    }
}
