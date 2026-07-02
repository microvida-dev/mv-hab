<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\InspectionStatus;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class InspectionTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('inspections.view')) {
            return [];
        }

        return PropertyInspection::query()
            ->whereDate('scheduled_for', today())
            ->whereIn('status', [
                InspectionStatus::Scheduled->value,
                InspectionStatus::InProgress->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(5)
            ->get()
            ->map(fn (PropertyInspection $inspection): TimelineEvent => new TimelineEvent(
                id: 'property-inspection-'.$inspection->getKey(),
                type: 'inspection',
                title: 'Vistoria técnica',
                description: trim(($inspection->inspection_number ?? 'Vistoria').' · '.$inspection->scheduled_for?->format('H:i')),
                route: 'backoffice.inspections.index',
                datetime: $inspection->scheduled_for,
                priority: 'medium',
                icon: 'inspection',
                tone: 'info',
                workspace: 'patrimony',
                metadata: [
                    'inspection_id' => $inspection->getKey(),
                    'inspection_number' => $inspection->inspection_number,
                    'status' => $inspection->status,
                ],
            ))
            ->all();
    }
}
