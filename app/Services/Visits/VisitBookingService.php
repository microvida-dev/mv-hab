<?php

namespace App\Services\Visits;

use App\Enums\InteractionType;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\HousingVisitStatusHistory;
use App\Models\User;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use App\Services\CandidateExperience\CandidateInteractionService;
use App\Services\Municipalities\VisitMunicipalContextService;
use App\Services\Workflows\WorkTaskCreationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitBookingService
{
    public function __construct(
        private readonly CandidateInteractionService $interactions,
        private readonly VisitNotificationService $notifications,
        private readonly VisitAuditService $audit,
        private readonly WorkTaskCreationService $tasks,
        private readonly VisitMunicipalContextService $municipalContext,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function book(User $candidate, array $data): HousingVisit
    {
        return DB::transaction(function () use ($candidate, $data): HousingVisit {
            $slot = VisitSlot::query()->whereKey($data['visit_slot_id'])->lockForUpdate()->firstOrFail();
            $this->ensureBookable($slot);

            $application = $this->applicationForCandidate($candidate, $data);
            $context = $this->municipalContext->bookingContext(
                $slot,
                $application,
                isset($data['contest_id'])
                    ? (int) $data['contest_id']
                    : null,
                isset($data['housing_unit_id'])
                    ? (int) $data['housing_unit_id']
                    : null,
            );
            $contest = $context['contest'];
            $housingUnit = $context['housing_unit'];

            $this->ensureContextIsVisitable($application, $contest, $housingUnit);
            $this->ensureNoDuplicate($candidate, $slot, $application);

            $slot->forceFill([
                'booked_count' => (int) $slot->booked_count + 1,
                'status' => ((int) $slot->booked_count + 1) >= (int) $slot->capacity
                    ? VisitSlotStatus::Full
                    : VisitSlotStatus::Reserved,
            ])->save();

            $visit = new HousingVisit([
                'candidate_notes' => $data['candidate_notes'] ?? null,
            ]);
            $visit->forceFill([
                'municipality_id' => $context['municipality_id'],
                'visit_number' => $this->nextNumber(),
                'visit_slot_id' => $slot->id,
                'application_id' => $application?->id,
                'contest_id' => $contest?->id,
                'housing_unit_id' => $housingUnit?->id,
                'candidate_user_id' => $candidate->id,
                'staff_user_id' => $slot->staff_user_id,
                'status' => VisitStatus::PendingConfirmation,
                'scheduled_at' => now(),
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'location' => $slot->location,
                'meeting_point' => $slot->meeting_point,
            ])->save();

            $this->history($visit, null, VisitStatus::PendingConfirmation, $candidate, 'Agendamento solicitado.');
            $this->interactions->record(
                user: $candidate,
                type: InteractionType::VisitScheduled,
                title: 'Visita solicitada',
                description: 'O pedido de visita foi registado na plataforma.',
                related: $visit,
                application: $application,
                contest: $contest,
                housingUnit: $housingUnit,
                actor: $candidate,
            );
            $this->audit->created($visit, $candidate);
            $this->createSchedulingTask($visit->refresh(), $candidate);
            $this->notifications->visitScheduled($visit->refresh(), $candidate);

            return $visit->refresh();
        });
    }

    public function confirm(HousingVisit $visit, User $actor): HousingVisit
    {
        return DB::transaction(function () use ($visit, $actor): HousingVisit {
            $visit = $this->lockedVisitForActor($visit, $actor);
            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));

            $visit->forceFill([
                'status' => VisitStatus::Confirmed,
                'confirmed_at' => now(),
                'staff_user_id' => $this->staffUserId($visit, $actor),
            ])->save();

            $this->history($visit, $from, VisitStatus::Confirmed, $actor, 'Visita confirmada.');
            $this->audit->updated($visit, $actor, 'Visita confirmada.');
            $this->notifications->visitConfirmed($visit->refresh(), $actor);

            return $visit->refresh();
        });
    }

    public function complete(HousingVisit $visit, User $actor, ?string $notes = null): HousingVisit
    {
        return DB::transaction(function () use ($visit, $actor, $notes): HousingVisit {
            $visit = $this->lockedVisitForActor($visit, $actor);
            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));
            $candidate = User::query()->findOrFail($visit->candidate_user_id);

            $visit->forceFill([
                'status' => VisitStatus::Completed,
                'completed_at' => now(),
                'staff_notes' => $notes,
                'staff_user_id' => $this->staffUserId($visit, $actor),
            ])->save();

            $this->history($visit, $from, VisitStatus::Completed, $actor, 'Visita concluída.', $notes);
            $this->interactions->record(
                user: $candidate,
                type: InteractionType::VisitCompleted,
                title: 'Visita concluída',
                description: 'A visita foi marcada como concluída.',
                related: $visit,
                application: $visit->application,
                contest: $visit->contest,
                housingUnit: $visit->housingUnit,
                actor: $actor,
            );
            $this->audit->updated($visit, $actor, 'Visita concluída.');
            $this->notifications->visitCompleted($visit->refresh(), $actor);

            return $visit->refresh();
        });
    }

    public function markNoShow(HousingVisit $visit, User $actor, string $notes): HousingVisit
    {
        return DB::transaction(function () use ($visit, $actor, $notes): HousingVisit {
            $visit = $this->lockedVisitForActor($visit, $actor);
            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));
            $candidate = User::query()->findOrFail($visit->candidate_user_id);

            $visit->forceFill([
                'status' => VisitStatus::Missed,
                'completed_at' => now(),
                'staff_notes' => $notes,
                'staff_user_id' => $this->staffUserId($visit, $actor),
            ])->save();

            $this->history($visit, $from, VisitStatus::Missed, $actor, 'Falta de comparência.', $notes);
            $this->interactions->record(
                user: $candidate,
                type: InteractionType::VisitNoShow,
                title: 'Falta de comparência registada',
                description: 'A visita foi marcada como falta de comparência.',
                related: $visit,
                application: $visit->application,
                contest: $visit->contest,
                housingUnit: $visit->housingUnit,
                actor: $actor,
            );
            $this->audit->updated($visit, $actor, 'Falta de comparência registada.');
            $this->notifications->visitNoShow($visit->refresh(), $actor);

            return $visit->refresh();
        });
    }

    public function reject(HousingVisit $visit, User $actor, ?string $reason = null): HousingVisit
    {
        return DB::transaction(function () use ($visit, $actor, $reason): HousingVisit {
            $visit = $this->lockedVisitForActor($visit, $actor);
            $from = VisitStatus::tryFrom((string) $visit->getRawOriginal('status'));

            $visit->forceFill([
                'status' => VisitStatus::Rejected,
                'staff_notes' => $reason,
                'staff_user_id' => $this->staffUserId($visit, $actor),
            ])->save();

            $this->history($visit, $from, VisitStatus::Rejected, $actor, 'Visita recusada.', $reason);
            $this->audit->updated($visit, $actor, 'Visita recusada.');

            return $visit->refresh();
        });
    }

    public function reserveSlot(VisitSlot $slot): void
    {
        $this->municipalContext->validateSlot($slot);
        $this->ensureBookable($slot);
        $nextCount = (int) $slot->booked_count + 1;
        $slot->forceFill([
            'booked_count' => $nextCount,
            'status' => $nextCount >= (int) $slot->capacity ? VisitSlotStatus::Full : VisitSlotStatus::Reserved,
        ])->save();
    }

    public function releaseSlot(VisitSlot $slot): void
    {
        $this->municipalContext->validateSlot($slot);
        $nextCount = max(0, (int) $slot->booked_count - 1);
        $slot->forceFill([
            'booked_count' => $nextCount,
            'status' => $nextCount === 0 ? VisitSlotStatus::Available : VisitSlotStatus::Reserved,
        ])->save();
    }

    private function ensureBookable(VisitSlot $slot): void
    {
        if (! $slot->isBookable()) {
            throw ValidationException::withMessages(['visit_slot_id' => 'O horário selecionado não está disponível.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applicationForCandidate(User $candidate, array $data): ?Application
    {
        if (empty($data['application_id'])) {
            return null;
        }

        $application = Application::query()->whereKey((int) $data['application_id'])->firstOrFail();

        if ($application->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['application_id' => 'A candidatura indicada não pertence ao candidato autenticado.']);
        }

        return $application;
    }

    private function ensureContextIsVisitable(?Application $application, ?Contest $contest, ?HousingUnit $housingUnit): void
    {
        if (! $application instanceof Application && ! $contest instanceof Contest && ! $housingUnit instanceof HousingUnit) {
            throw ValidationException::withMessages(['visit' => 'A visita deve estar associada a candidatura, concurso ou habitação.']);
        }

        if ($housingUnit instanceof HousingUnit && ! $housingUnit->is_public && ! $this->housingUnitBelongsToContest($housingUnit, $contest)) {
            throw ValidationException::withMessages(['housing_unit_id' => 'A habitação indicada não está disponível para visita.']);
        }
    }

    private function housingUnitBelongsToContest(HousingUnit $housingUnit, ?Contest $contest): bool
    {
        if (! $contest instanceof Contest) {
            return false;
        }

        return $housingUnit->contestHousingUnits()
            ->where('contest_id', $contest->id)
            ->exists();
    }

    private function ensureNoDuplicate(User $candidate, VisitSlot $slot, ?Application $application): void
    {
        $activeStatuses = array_map(
            static fn (VisitStatus $status): string => $status->value,
            [VisitStatus::Requested, VisitStatus::PendingConfirmation, VisitStatus::Confirmed, VisitStatus::Rescheduled],
        );

        if (HousingVisit::query()->forCandidate($candidate)->where('visit_slot_id', $slot->id)->whereIn('status', $activeStatuses)->exists()) {
            throw ValidationException::withMessages(['visit_slot_id' => 'Já existe uma reserva ativa para este horário.']);
        }

        if ($application instanceof Application) {
            $max = (int) config('mvhab.candidate_support.max_active_visits_per_application', 3);
            $active = HousingVisit::query()
                ->forCandidate($candidate)
                ->where('application_id', $application->id)
                ->whereIn('status', $activeStatuses)
                ->count();

            if ($active >= $max) {
                throw ValidationException::withMessages(['application_id' => 'Foi atingido o limite de visitas ativas para esta candidatura.']);
            }
        }
    }

    private function nextNumber(): string
    {
        $next = (int) HousingVisit::query()->withTrashed()->max('id') + 1;

        return 'VIS-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function history(HousingVisit $visit, ?VisitStatus $from, VisitStatus $to, User $actor, ?string $reason = null, ?string $notes = null): void
    {
        HousingVisitStatusHistory::query()->create([
            'housing_visit_id' => $visit->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'changed_by' => $actor->id,
            'reason' => $reason,
            'notes' => $notes,
            'changed_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function createSchedulingTask(HousingVisit $visit, User $actor): void
    {
        $this->tasks->createFromSource(
            type: WorkTask::TYPE_VISIT_SCHEDULE,
            related: $visit,
            actor: $actor,
            source: $this->workTaskSource($visit),
            priority: WorkTask::PRIORITY_NORMAL,
            metadata: [
                'visit_id' => $visit->id,
                'contest_id' => $visit->contest_id,
                'housing_unit_id' => $visit->housing_unit_id,
                'status' => $visit->getRawOriginal('status'),
                'channel' => 'candidate_portal',
            ],
        );
    }

    private function workTaskSource(HousingVisit $visit): string
    {
        return 'housing_visit:'.$visit->id;
    }

    private function lockedVisitForActor(
        HousingVisit $visit,
        User $actor,
    ): HousingVisit {
        $lockedVisit = HousingVisit::query()
            ->whereKey($visit)
            ->lockForUpdate()
            ->firstOrFail();
        $this->municipalContext->validateVisitForActor(
            $lockedVisit,
            $actor,
        );

        return $lockedVisit;
    }

    private function staffUserId(
        HousingVisit $visit,
        User $actor,
    ): ?int {
        if ($visit->staff_user_id !== null) {
            return (int) $visit->staff_user_id;
        }

        return $actor->municipality_id !== null
            && (int) $actor->municipality_id
                === (int) $visit->municipality_id
                    ? (int) $actor->id
                    : null;
    }
}
