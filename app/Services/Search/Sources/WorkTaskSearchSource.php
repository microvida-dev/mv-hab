<?php

namespace App\Services\Search\Sources;

use App\Models\User;
use App\Models\WorkTask;
use App\Services\Search\Contracts\SearchSource;
use App\Services\Search\SearchResultAuthorizationService;
use App\Services\Workflows\WorkTaskDashboardService;
use Illuminate\Database\Eloquent\Builder;

class WorkTaskSearchSource implements SearchSource
{
    public function __construct(
        private readonly SearchResultAuthorizationService $authorization,
        private readonly WorkTaskDashboardService $dashboard,
    ) {}

    public function key(): string
    {
        return 'work_task';
    }

    public function label(): string
    {
        return 'Tarefas';
    }

    public function minimumCharacters(): int
    {
        return 2;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(User $user, string $term, int $limit): array
    {
        if (! $this->authorization->canAccess($user, 'backoffice.work-tasks.show', 'work_tasks.view')) {
            return [];
        }

        return array_values($this->dashboard->visibleQuery($user)
            ->select(['id', 'task_number', 'type', 'status', 'priority', 'municipal_team_id', 'assigned_user_id', 'due_at', 'created_at'])
            ->where(function (Builder $query) use ($term): void {
                $query->where('task_number', 'like', '%'.$term.'%')
                    ->orWhere('type', 'like', '%'.$term.'%')
                    ->orWhere('status', 'like', '%'.$term.'%');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (WorkTask $task): array => [
                'type' => 'work_task',
                'group_key' => 'work_tasks',
                'group_label' => $this->label(),
                'label' => WorkTask::typeLabel((string) $task->type).' · '.$task->task_number,
                'subtitle' => WorkTask::statusLabel((string) $task->status).' · Prioridade '.(string) $task->priority,
                'route_name' => 'backoffice.work-tasks.show',
                'route_parameters' => [$task->getRouteKey()],
                'score' => 88,
            ])
            ->all());
    }
}
