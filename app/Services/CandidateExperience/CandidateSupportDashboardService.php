<?php

namespace App\Services\CandidateExperience;

use App\Enums\InconsistencySeverity;
use App\Enums\TicketStatus;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\HousingVisit;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\VisitSlot;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CandidateSupportDashboardService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @return array<string, int|float|array<string, int>>
     */
    public function indicators(User $actor): array
    {
        $visits = $this->municipalScope->housingVisits(
            HousingVisit::query(),
            $actor,
        );
        $slots = $this->municipalScope->visitSlots(
            VisitSlot::query(),
            $actor,
        );
        $scheduled = (clone $visits)->whereIn('status', [
            VisitStatus::Requested->value,
            VisitStatus::PendingConfirmation->value,
            VisitStatus::Confirmed->value,
            VisitStatus::Rescheduled->value,
        ])->count();
        $completed = (clone $visits)
            ->where('status', VisitStatus::Completed->value)
            ->count();
        $missed = (clone $visits)
            ->where('status', VisitStatus::Missed->value)
            ->count();

        return [
            'visits_scheduled' => $scheduled,
            'visits_confirmed' => (clone $visits)
                ->where('status', VisitStatus::Confirmed->value)
                ->count(),
            'visits_cancelled' => (clone $visits)
                ->whereIn('status', [
                    VisitStatus::CancelledByCandidate->value,
                    VisitStatus::CancelledByStaff->value,
                ])
                ->count(),
            'visits_completed' => $completed,
            'miss_rate' => ($completed + $missed) > 0 ? round(($missed / ($completed + $missed)) * 100, 2) : 0.0,
            'slots_available' => (clone $slots)
                ->where('status', VisitSlotStatus::Available->value)
                ->count(),
            'slots_full' => (clone $slots)
                ->where('status', VisitSlotStatus::Full->value)
                ->count(),
            'tickets_open' => SupportTicket::query()->whereIn('status', [TicketStatus::Open->value, TicketStatus::InProgress->value, TicketStatus::Reopened->value])->count(),
            'tickets_pending_candidate' => SupportTicket::query()->where('status', TicketStatus::PendingCandidate->value)->count(),
            'tickets_pending_staff' => SupportTicket::query()->where('status', TicketStatus::PendingStaff->value)->count(),
            'tickets_by_category' => $this->ticketCountsBy('category'),
            'open_inconsistencies' => ApplicationSimulationInconsistency::query()->open()->count(),
            'inconsistencies_by_severity' => $this->inconsistencyCountsBySeverity(),
        ];
    }

    /**
     * @param  literal-string  $field
     * @return array<string, int>
     */
    private function ticketCountsBy(string $field): array
    {
        return SupportTicket::query()
            ->selectRaw($field.', count(*) as aggregate')
            ->groupBy($field)
            ->pluck('aggregate', $field)
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function inconsistencyCountsBySeverity(): array
    {
        $counts = ApplicationSimulationInconsistency::query()
            ->open()
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        foreach (InconsistencySeverity::cases() as $severity) {
            $counts[$severity->value] ??= 0;
        }

        return $counts;
    }
}
