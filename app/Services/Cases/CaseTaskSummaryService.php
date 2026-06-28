<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseTaskData;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class CaseTaskSummaryService
{
    public function __construct(private readonly CaseAuthorizationService $authorization) {}

    /**
     * @return list<CaseTaskData>
     */
    public function forCase(User $user, Model $case, int $limit = 8): array
    {
        if (! Schema::hasTable('work_tasks') || ! $this->authorization->hasPermission($user, 'work_tasks.view')) {
            return [];
        }

        $route = Route::has('backoffice.work-tasks.show') ? 'backoffice.work-tasks.show' : null;

        return array_values(WorkTask::query()
            ->select(['id', 'task_number', 'type', 'status', 'priority', 'due_at', 'related_type', 'related_id'])
            ->where('related_type', $case::class)
            ->where('related_id', $case->getKey())
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(fn (WorkTask $task): CaseTaskData => new CaseTaskData(
                label: WorkTask::typeLabel((string) $task->type),
                status: WorkTask::statusLabel((string) $task->status),
                priority: $this->priorityLabel((string) $task->priority),
                dueAt: $this->asCarbon($task->due_at),
                route: $route,
                parameters: $route !== null ? [$task] : [],
            ))
            ->values()
            ->all());
    }

    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            WorkTask::PRIORITY_URGENT => 'Urgente',
            WorkTask::PRIORITY_HIGH => 'Alta',
            WorkTask::PRIORITY_LOW => 'Baixa',
            default => 'Normal',
        };
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return $value === null ? null : Carbon::parse($value);
    }
}
