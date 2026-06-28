<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Models\WorkTask;
use App\Services\Workflows\WorkTaskDashboardService;
use Illuminate\Database\Eloquent\Builder;

class SmartActionCenterService
{
    public function __construct(
        private readonly WorkTaskDashboardService $tasks,
        private readonly ProductivityPresenter $presenter,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return list<array{key: string, title: string, description: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user, int $limit = 5): array
    {
        if (! $this->authorization->canUseBackofficeProductivity($user)) {
            return [];
        }

        return array_values(array_filter([
            $this->section('today', 'Hoje', 'Itens com prazo para hoje.', $this->today($user, $limit)),
            $this->section('overdue', 'Em atraso', 'Itens com SLA ou prazo ultrapassado.', $this->overdue($user, $limit)),
            $this->section('due-soon', 'A vencer', 'Itens a vencer nas próximas 48 horas.', $this->dueSoon($user, $limit)),
            $this->section('this-week', 'Esta semana', 'Itens com prazo até ao fim da semana.', $this->thisWeek($user, $limit)),
            $this->section('completed-today', 'Concluído hoje', 'Itens concluídos hoje.', $this->completedToday($user, $limit)),
            $this->section('unassigned', 'Sem responsável', 'Itens ainda sem técnico atribuído.', $this->unassigned($user, $limit)),
            $this->section('blocked', 'Bloqueados', 'Itens em espera operacional.', $this->blocked($user, $limit)),
        ], fn (array $section): bool => $section['items'] !== []));
    }

    /**
     * @return Builder<WorkTask>
     */
    public function baseQuery(User $user): Builder
    {
        return $this->tasks->visibleQuery($user)
            ->select(['id', 'task_number', 'type', 'status', 'priority', 'municipal_team_id', 'assigned_user_id', 'due_at', 'completed_at', 'created_at']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function overdue(User $user, int $limit = 5): array
    {
        return $this->present($user, $this->active($this->baseQuery($user))
            ->where(function (Builder $query): void {
                $query->where('status', WorkTask::STATUS_OVERDUE)
                    ->orWhere('due_at', '<', now());
            }), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dueSoon(User $user, int $limit = 5): array
    {
        return $this->present($user, $this->active($this->baseQuery($user))
            ->whereBetween('due_at', [now(), now()->addDays(2)]), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function today(User $user, int $limit): array
    {
        return $this->present($user, $this->active($this->baseQuery($user))
            ->whereDate('due_at', today()), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function thisWeek(User $user, int $limit): array
    {
        return $this->present($user, $this->active($this->baseQuery($user))
            ->whereBetween('due_at', [now(), now()->endOfWeek()]), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function completedToday(User $user, int $limit): array
    {
        return $this->present($user, $this->baseQuery($user)
            ->where('status', WorkTask::STATUS_COMPLETED)
            ->whereDate('completed_at', today()), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function unassigned(User $user, int $limit): array
    {
        if (! $this->authorization->canSeeWorkload($user)) {
            return [];
        }

        return $this->present($user, $this->active($this->baseQuery($user))
            ->whereNull('assigned_user_id'), $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocked(User $user, int $limit): array
    {
        return $this->present($user, $this->baseQuery($user)
            ->whereIn('status', [
                WorkTask::STATUS_WAITING_CANDIDATE,
                WorkTask::STATUS_WAITING_INTERNAL,
                WorkTask::STATUS_WAITING_EXTERNAL,
            ]), $limit);
    }

    /**
     * @param  Builder<WorkTask>  $query
     * @return Builder<WorkTask>
     */
    private function active(Builder $query): Builder
    {
        return $query->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{key: string, title: string, description: string, items: list<array<string, mixed>>}
     */
    private function section(string $key, string $title, string $description, array $items): array
    {
        return compact('key', 'title', 'description', 'items');
    }

    /**
     * @param  Builder<WorkTask>  $query
     * @return list<array<string, mixed>>
     */
    private function present(User $user, Builder $query, int $limit): array
    {
        $items = $query
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('due_at')
            ->limit($limit)
            ->get()
            ->map(fn (WorkTask $task): ?array => $this->presenter->workTask($user, $task))
            ->filter()
            ->values()
            ->all();

        return array_values($items);
    }
}
