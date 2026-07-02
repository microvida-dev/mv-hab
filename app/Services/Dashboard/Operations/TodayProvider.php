<?php

namespace App\Services\Dashboard\Operations;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\InspectionStatus;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Dashboard\Timeline\TimelineAggregatorService;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;
use Illuminate\Support\Carbon;

class TodayProvider
{
    public function __construct(
        private readonly ?TimelineAggregatorService $timelineAggregator = null,
    ) {}

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user, array $dashboard): array
    {
        $timeline = $this->timeline($user, $dashboard);

        return $timeline['items'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    public function timelineForUser(User $user, array $dashboard): array
    {
        return $this->timeline($user, $dashboard);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<string, mixed>
     */
    private function timeline(User $user, array $dashboard): array
    {
        $aggregator = $this->timelineAggregator ?? new TimelineAggregatorService([
            new class($this) implements TimelineProviderInterface {
                public function __construct(
                    private readonly TodayProvider $provider,
                ) {}

                public function forUser(User $user, array $dashboard = []): array
                {
                    return $this->provider->eventsForUser($user, $dashboard);
                }
            },
        ]);

        return $aggregator->forUser($user, $dashboard);
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, TimelineEvent>
     */
    public function eventsForUser(User $user, array $dashboard): array
    {
        return collect()
            ->merge($this->assignedTasks($user))
            ->merge($this->housingVisits($user))
            ->merge($this->propertyInspections($user))
            ->merge($this->deadlineItems($dashboard))
            ->values()
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function assignedTasks(User $user): array
    {
        if (! $user->hasPermission('work_tasks.view')) {
            return [];
        }

        return WorkTask::query()
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', [
                WorkTask::STATUS_COMPLETED,
                WorkTask::STATUS_CANCELLED,
            ])
            ->orderByRaw('due_at IS NULL, due_at ASC')
            ->limit(5)
            ->get()
            ->map(fn (WorkTask $task): TimelineEvent => $this->event(
                id: 'work-task-'.$task->getKey(),
                type: 'task',
                title: WorkTask::typeLabel((string) $task->type),
                description: trim(($task->task_number ?? 'Tarefa').' · '.WorkTask::statusLabel((string) $task->status)),
                route: 'backoffice.work-tasks.my',
                icon: 'check',
                tone: in_array($task->priority, [WorkTask::PRIORITY_HIGH, WorkTask::PRIORITY_URGENT], true) ? 'danger' : 'warning',
                datetime: $task->due_at,
                priority: match ((string) $task->priority) {
                    WorkTask::PRIORITY_URGENT => 'critical',
                    WorkTask::PRIORITY_HIGH => 'high',
                    WorkTask::PRIORITY_NORMAL => 'medium',
                    default => 'low',
                },
                workspace: 'operations',
                metadata: [
                    'task_id' => $task->getKey(),
                    'task_number' => $task->task_number,
                    'status' => $task->status,
                ],
            ))
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function housingVisits(User $user): array
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
            ->map(fn (HousingVisit $visit): TimelineEvent => $this->event(
                id: 'housing-visit-'.$visit->getKey(),
                type: 'visit',
                title: 'Visita agendada',
                description: trim(($visit->visit_number ?? 'Visita').' · '.$visit->scheduled_at?->format('H:i')),
                route: 'backoffice.housing-visits.index',
                icon: 'user-inspection',
                tone: 'info',
                datetime: $visit->scheduled_at,
                priority: 'medium',
                workspace: 'patrimony',
                metadata: [
                    'visit_id' => $visit->getKey(),
                    'visit_number' => $visit->visit_number,
                    'status' => $visit->status,
                ],
            ))
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function propertyInspections(User $user): array
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
            ->map(fn (PropertyInspection $inspection): TimelineEvent => $this->event(
                id: 'property-inspection-'.$inspection->getKey(),
                type: 'inspection',
                title: 'Vistoria técnica',
                description: trim(($inspection->inspection_number ?? 'Vistoria').' · '.$inspection->scheduled_for?->format('H:i')),
                route: 'backoffice.inspections.index',
                icon: 'inspection',
                tone: 'info',
                datetime: $inspection->scheduled_for,
                priority: 'medium',
                workspace: 'patrimony',
                metadata: [
                    'inspection_id' => $inspection->getKey(),
                    'inspection_number' => $inspection->inspection_number,
                    'status' => $inspection->status,
                ],
            ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, TimelineEvent>
     */
    private function deadlineItems(array $dashboard): array
    {
        return collect($dashboard['deadlines'] ?? [])
            ->map(fn (array $deadline, int $index): TimelineEvent => $this->event(
                id: 'deadline-'.$index.'-'.md5((string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo')),
                type: 'deadline',
                title: (string) ($deadline['label'] ?? $deadline['title'] ?? 'Prazo'),
                description: (string) ($deadline['description'] ?? ''),
                route: (string) ($deadline['route'] ?? 'dashboard'),
                icon: 'calendar',
                tone: (string) ($deadline['tone'] ?? 'neutral'),
                datetime: null,
                priority: 'low',
                workspace: 'operations',
                metadata: $deadline,
            ))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function event(
        string $id,
        string $type,
        string $title,
        ?string $description,
        ?string $route,
        string $icon,
        string $tone,
        ?Carbon $datetime,
        string $priority,
        ?string $workspace = null,
        string $status = 'pending',
        array $metadata = [],
    ): TimelineEvent {
        return new TimelineEvent(
            id: $id,
            type: $type,
            title: $title,
            description: $description,
            route: $route,
            datetime: $datetime,
            priority: $priority,
            status: $status,
            icon: $icon,
            tone: $tone,
            workspace: $workspace,
            metadata: $metadata,
        );
    }
}
