<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class VisitTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('visits.view')) {
            return [];
        }

        return HousingVisit::query()
            ->whereDate('scheduled_at', today())
            ->whereIn('status', [
                VisitStatus::Requested->value,
                VisitStatus::PendingConfirmation->value,
                VisitStatus::Confirmed->value,
                VisitStatus::Rescheduled->value,
            ])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(fn (HousingVisit $visit): TimelineEvent => new TimelineEvent(
                id: 'housing-visit-'.$visit->getKey(),
                type: 'visit',
                title: 'Visita agendada',
                description: trim(($visit->visit_number ?? 'Visita').' · '.$visit->scheduled_at?->format('H:i')),
                route: 'backoffice.housing-visits.index',
                datetime: $visit->scheduled_at,
                priority: 'medium',
                icon: 'user-inspection',
                tone: 'info',
                workspace: 'patrimony',
                metadata: [
                    'visit_id' => $visit->getKey(),
                    'visit_number' => $visit->visit_number,
                    'status' => $visit->status,
                ],
            ))
            ->all();
    }
}
