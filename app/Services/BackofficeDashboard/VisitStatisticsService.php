<?php

namespace App\Services\BackofficeDashboard;

use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class VisitStatisticsService
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(User $actor, array $filters = []): array
    {
        $query = $this->municipalScope->housingVisits(
            HousingVisit::query(),
            $actor,
        );

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
