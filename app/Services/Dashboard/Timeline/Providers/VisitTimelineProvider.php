<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Municipalities\MunicipalRecordScopeService;

class VisitTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('visits.view')) {
            return [];
        }

        return $this->municipalScope
            ->housingVisits(
                HousingVisit::query(),
                $user,
            )
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
            ->map(
                fn (
                    HousingVisit $visit,
                ): TimelineEvent => $this->factory->make(
                    id: 'housing-visit-'.$visit->getKey(),
                    type: TimelineType::Visit,
                    title: 'Visita agendada',
                    description: trim(
                        ($visit->visit_number ?? 'Visita')
                        .' · '
                        .$visit->scheduled_at?->format('H:i'),
                    ),
                    route: route('backoffice.housing-visits.index'),
                    datetime: $visit->scheduled_at,
                    priority: TimelinePriority::Medium,
                    icon: 'user-inspection',
                    tone: 'info',
                    workspace: TimelineWorkspace::Patrimony,
                    metadata: [
                        'visit_id' => $visit->getKey(),
                        'visit_number' => $visit->visit_number,
                        'status' => $visit->status->value,
                    ],
                ),
            )
            ->all();
    }
}
