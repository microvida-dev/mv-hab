<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\User;
use App\Models\WorkTask;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class WorkTaskTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
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
            ->map(fn (WorkTask $task): TimelineEvent => new TimelineEvent(
                id: 'work-task-'.$task->getKey(),
                type: TimelineType::Task,
                title: WorkTask::typeLabel((string) $task->type),
                description: trim(($task->task_number ?? 'Tarefa').' · '.WorkTask::statusLabel((string) $task->status)),
                route: 'backoffice.work-tasks.my',
                datetime: $task->due_at,
                priority: match ((string) $task->priority) {
                    WorkTask::PRIORITY_URGENT => TimelinePriority::Critical,
                    WorkTask::PRIORITY_HIGH => TimelinePriority::High,
                    WorkTask::PRIORITY_NORMAL => TimelinePriority::Medium,
                    default => TimelinePriority::Low,
                },
                icon: 'check',
                tone: in_array($task->priority, [WorkTask::PRIORITY_HIGH, WorkTask::PRIORITY_URGENT], true) ? 'danger' : 'warning',
                workspace: TimelineWorkspace::Operations,
                metadata: [
                    'task_id' => $task->getKey(),
                    'task_number' => $task->task_number,
                    'status' => $task->status,
                ],
            ))
            ->all();
    }
}
