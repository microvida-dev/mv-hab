<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\MaintenanceInterventionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;
use App\Services\Municipalities\MunicipalRecordScopeService;

class MaintenanceTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function forUser(
        User $user,
        array $dashboard = [],
    ): array {
        if (! $user->hasPermission('maintenance_requests.view')) {
            return [];
        }

        return collect()
            ->merge($this->scheduledRequests($user))
            ->merge($this->scheduledInterventions($user))
            ->values()
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function scheduledRequests(User $user): array
    {
        return $this->municipalScope
            ->maintenanceRequests(
                MaintenanceRequest::query(),
                $user,
            )
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', [
                MaintenanceRequestStatus::Closed->value,
                MaintenanceRequestStatus::Cancelled->value,
                MaintenanceRequestStatus::Resolved->value,
                MaintenanceRequestStatus::Rejected->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(
                fn (
                    MaintenanceRequest $request,
                ): TimelineEvent => $this->factory->make(
                    id: 'maintenance-request-'
                        .$request->getKey(),
                    type: TimelineType::MaintenanceRequest,
                    title: 'Manutenção agendada',
                    description: trim(
                        (
                            $request->request_number
                            ?? 'Pedido de manutenção'
                        )
                        .' · '
                        .$request->title,
                    ),
                    route: route(
                        'backoffice.maintenance.requests.index',
                    ),
                    datetime: $request->scheduled_for,
                    priority: $request->scheduled_for?->isPast()
                        ? TimelinePriority::High
                        : TimelinePriority::Medium,
                    icon: 'maintenance',
                    tone: $request->scheduled_for?->isPast()
                        ? 'warning'
                        : 'info',
                    workspace: TimelineWorkspace::Maintenance,
                    metadata: [
                        'maintenance_request_id' => $request->getKey(),
                        'request_number' => $request->request_number,
                        'status' => $request->status->value,
                    ],
                ),
            )
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function scheduledInterventions(
        User $user,
    ): array {
        return $this->municipalScope
            ->maintenanceInterventions(
                MaintenanceIntervention::query(),
                $user,
            )
            ->with('maintenanceRequest')
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', [
                MaintenanceInterventionStatus::Completed->value,
                MaintenanceInterventionStatus::Cancelled->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(
                fn (
                    MaintenanceIntervention $intervention,
                ): TimelineEvent => $this->interventionEvent(
                    $intervention,
                ),
            )
            ->all();
    }

    private function interventionEvent(
        MaintenanceIntervention $intervention,
    ): TimelineEvent {
        $request = $intervention->maintenanceRequest;

        $requestNumber = $request instanceof MaintenanceRequest
            ? $request->request_number
            : null;

        $description = $request instanceof MaintenanceRequest
            ? trim(
                $request->request_number
                .' · '
                .$request->title,
            )
            : trim(
                'Intervenção · '
                .(
                    $intervention->work_description
                    ?? 'Manutenção'
                ),
            );

        return $this->factory->make(
            id: 'maintenance-intervention-'
                .$intervention->getKey(),
            type: TimelineType::MaintenanceIntervention,
            title: 'Intervenção de manutenção',
            description: $description,
            route: route(
                'backoffice.maintenance.requests.index',
            ),
            datetime: $intervention->scheduled_for,
            priority: $intervention->scheduled_for?->isPast()
                ? TimelinePriority::High
                : TimelinePriority::Medium,
            icon: 'maintenance',
            tone: $intervention->scheduled_for?->isPast()
                ? 'warning'
                : 'info',
            workspace: TimelineWorkspace::Maintenance,
            metadata: [
                'maintenance_intervention_id' => $intervention->getKey(),
                'maintenance_request_id' => $intervention->maintenance_request_id,
                'request_number' => $requestNumber,
                'status' => $intervention->status->value,
            ],
        );
    }
}
