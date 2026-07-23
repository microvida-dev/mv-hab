<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class MaintenanceTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('maintenance_requests.view')) {
            return [];
        }

        return collect()
            ->merge($this->scheduledRequests())
            ->merge($this->scheduledInterventions())
            ->values()
            ->all();
    }

    private function scheduledRequests(): array
    {
        return MaintenanceRequest::query()
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', ['closed', 'cancelled', 'resolved', 'rejected'])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(fn (MaintenanceRequest $request): TimelineEvent => $this->factory->make(
                id: 'maintenance-request-'.$request->getKey(),
                type: TimelineType::MaintenanceRequest,
                title: 'Manutenção agendada',
                description: trim(($request->request_number ?? 'Pedido de manutenção').' · '.$request->title),
                route: route('backoffice.maintenance.requests.index'),
                datetime: $request->scheduled_for,
                priority: $request->scheduled_for?->isPast()
                    ? TimelinePriority::High
                    : TimelinePriority::Medium,
                icon: 'maintenance',
                tone: $request->scheduled_for?->isPast() ? 'warning' : 'info',
                workspace: TimelineWorkspace::Maintenance,
                metadata: [
                    'maintenance_request_id' => $request->getKey(),
                    'request_number' => $request->request_number,
                    'status' => $request->status,
                ],
            ))
            ->all();
    }

    private function scheduledInterventions(): array
    {
        return MaintenanceIntervention::query()
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(fn (MaintenanceIntervention $intervention): TimelineEvent => $this->factory->make(
                id: 'maintenance-intervention-'.$intervention->getKey(),
                type: TimelineType::MaintenanceIntervention,
                title: 'Intervenção de manutenção',
                description: trim(($intervention->intervention_number ?? 'Intervenção').' · '.$intervention->title),
                route: route('backoffice.maintenance.requests.index'),
                datetime: $intervention->scheduled_for,
                priority: $intervention->scheduled_for?->isPast()
                    ? TimelinePriority::High
                    : TimelinePriority::Medium,
                icon: 'maintenance',
                tone: $intervention->scheduled_for?->isPast() ? 'warning' : 'info',
                workspace: TimelineWorkspace::Maintenance,
                metadata: [
                    'maintenance_intervention_id' => $intervention->getKey(),
                    'intervention_number' => $intervention->intervention_number,
                    'status' => $intervention->status,
                ],
            ))
            ->all();
    }
}
