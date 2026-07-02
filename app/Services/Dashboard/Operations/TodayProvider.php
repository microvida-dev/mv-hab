<?php

namespace App\Services\Dashboard\Operations;

use App\Models\User;
use App\Models\WorkTask;

class TodayProvider
{
    /**
     * @param  array<string, mixed>  $dashboard
     * @return array<int, array<string, mixed>>
     */
    public function forUser(User $user, array $dashboard): array
    {
        return collect()
            ->merge($this->assignedTasks($user))
            ->merge($dashboard['deadlines'] ?? [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
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
            ->map(fn (WorkTask $task): array => [
                'title' => WorkTask::typeLabel((string) $task->type),
                'description' => trim(($task->task_number ?? 'Tarefa').' · '.WorkTask::statusLabel((string) $task->status)),
                'route' => 'backoffice.work-tasks.my',
                'icon' => 'check',
                'tone' => in_array($task->priority, [WorkTask::PRIORITY_HIGH, WorkTask::PRIORITY_URGENT], true) ? 'danger' : 'warning',
            ])
            ->all();
    }
}
