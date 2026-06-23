<?php

namespace App\Services\CandidateExperience;

use App\Enums\InconsistencySeverity;
use App\Enums\TicketStatus;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\ApplicationSimulationInconsistency;
use App\Models\HousingVisit;
use App\Models\SupportTicket;
use App\Models\VisitSlot;

class CandidateSupportDashboardService
{
    /**
     * @return array<string, int|float|array<string, int>>
     */
    public function indicators(): array
    {
        $scheduled = HousingVisit::query()->whereIn('status', [
            VisitStatus::Requested->value,
            VisitStatus::PendingConfirmation->value,
            VisitStatus::Confirmed->value,
            VisitStatus::Rescheduled->value,
        ])->count();
        $completed = HousingVisit::query()->where('status', VisitStatus::Completed->value)->count();
        $missed = HousingVisit::query()->where('status', VisitStatus::Missed->value)->count();

        return [
            'visits_scheduled' => $scheduled,
            'visits_confirmed' => HousingVisit::query()->where('status', VisitStatus::Confirmed->value)->count(),
            'visits_cancelled' => HousingVisit::query()->whereIn('status', [VisitStatus::CancelledByCandidate->value, VisitStatus::CancelledByStaff->value])->count(),
            'visits_completed' => $completed,
            'miss_rate' => ($completed + $missed) > 0 ? round(($missed / ($completed + $missed)) * 100, 2) : 0.0,
            'slots_available' => VisitSlot::query()->where('status', VisitSlotStatus::Available->value)->count(),
            'slots_full' => VisitSlot::query()->where('status', VisitSlotStatus::Full->value)->count(),
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
