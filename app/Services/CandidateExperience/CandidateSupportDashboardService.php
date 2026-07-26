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
use Illuminate\Database\Eloquent\Builder;

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
        $tickets = $this->municipalScope->supportTickets(
            SupportTicket::query(),
            $actor,
        );
        $inconsistencies = $this->municipalScope
            ->applicationSimulationInconsistencies(
                ApplicationSimulationInconsistency::query(),
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
            'tickets_open' => (clone $tickets)->whereIn('status', [TicketStatus::Open->value, TicketStatus::InProgress->value, TicketStatus::Reopened->value])->count(),
            'tickets_pending_candidate' => (clone $tickets)->where('status', TicketStatus::PendingCandidate->value)->count(),
            'tickets_pending_staff' => (clone $tickets)->where('status', TicketStatus::PendingStaff->value)->count(),
            'tickets_by_category' => $this->ticketCountsBy($tickets, 'category'),
            'open_inconsistencies' => (clone $inconsistencies)->open()->count(),
            'inconsistencies_by_severity' => $this->inconsistencyCountsBySeverity($inconsistencies),
        ];
    }

    /**
     * @param  Builder<SupportTicket>  $query
     * @param  literal-string  $field
     * @return array<string, int>
     */
    private function ticketCountsBy(
        Builder $query,
        string $field,
    ): array {
        return $query
            ->selectRaw($field.', count(*) as aggregate')
            ->groupBy($field)
            ->pluck('aggregate', $field)
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  Builder<ApplicationSimulationInconsistency>  $query
     * @return array<string, int>
     */
    private function inconsistencyCountsBySeverity(
        Builder $query,
    ): array {
        $counts = $query
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
