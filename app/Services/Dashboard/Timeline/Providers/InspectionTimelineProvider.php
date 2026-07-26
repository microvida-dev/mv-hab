<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\InspectionStatus;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Municipalities\MunicipalRecordScopeService;

class InspectionTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function forUser(
        User $user,
        array $dashboard = [],
    ): array {
        if (! $user->hasPermission('inspections.view')) {
            return [];
        }

        return $this->municipalScope
            ->propertyInspections(
                PropertyInspection::query(),
                $user,
            )
            ->whereDate('scheduled_for', today())
            ->whereIn('status', [
                InspectionStatus::Scheduled->value,
                InspectionStatus::InProgress->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(5)
            ->get()
            ->map(
                fn (
                    PropertyInspection $inspection,
                ): TimelineEvent => $this->factory->make(
                    id: 'property-inspection-'
                        .$inspection->getKey(),
                    type: TimelineType::Inspection,
                    title: 'Vistoria técnica',
                    description: trim(
                        (
                            $inspection->inspection_number
                            ?? 'Vistoria'
                        )
                        .' · '
                        .$inspection->scheduled_for?->format('H:i'),
                    ),
                    route: route(
                        'backoffice.inspections.index',
                    ),
                    datetime: $inspection->scheduled_for,
                    priority: TimelinePriority::Medium,
                    icon: 'inspection',
                    tone: 'info',
                    workspace: TimelineWorkspace::Patrimony,
                    metadata: [
                        'inspection_id' => $inspection->getKey(),
                        'inspection_number' => $inspection->inspection_number,
                        'status' => $inspection->status->value,
                    ],
                ),
            )
            ->all();
    }
}
