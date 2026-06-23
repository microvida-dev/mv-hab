<?php

namespace App\Services\BackofficeDashboard;

use App\Enums\VisitStatus;
use App\Models\HousingVisit;

class VisitStatisticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(array $filters = []): array
    {
        $query = HousingVisit::query();

        if (! empty($filters['contest_id'])) {
            $query->where('contest_id', (int) $filters['contest_id']);
        }

        return [
            'scheduled' => (clone $query)->whereIn('status', [
                VisitStatus::Requested->value,
                VisitStatus::PendingConfirmation->value,
                VisitStatus::Confirmed->value,
                VisitStatus::Rescheduled->value,
            ])->count(),
            'confirmed' => (clone $query)->where('status', VisitStatus::Confirmed->value)->count(),
            'completed' => (clone $query)->where('status', VisitStatus::Completed->value)->count(),
            'cancelled' => (clone $query)->whereIn('status', [
                VisitStatus::CancelledByCandidate->value,
                VisitStatus::CancelledByStaff->value,
            ])->count(),
            'missed' => (clone $query)->where('status', VisitStatus::Missed->value)->count(),
        ];
    }
}
