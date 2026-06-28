<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Builder;

class MyWorkService
{
    public function __construct(
        private readonly SmartActionCenterService $actionCenter,
        private readonly ProductivityPresenter $presenter,
        private readonly ProductivityAuthorizationService $authorization,
    ) {}

    /**
     * @return list<array{key: string, title: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user, int $limit = 8): array
    {
        if (! $this->authorization->canUseBackofficeProductivity($user)) {
            return [];
        }

        $teamIds = $user->municipalTeams()
            ->wherePivotNull('left_at')
            ->pluck('municipal_teams.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return array_values(array_filter([
            [
                'key' => 'assigned',
                'title' => 'Atribuído a mim',
                'items' => $this->present($user, $this->actionCenter->baseQuery($user)->where('assigned_user_id', $user->id), $limit),
            ],
            [
                'key' => 'team',
                'title' => 'Fila da minha equipa',
                'items' => $this->present($user, $this->actionCenter->baseQuery($user)->whereIn('municipal_team_id', $teamIds), $limit),
            ],
            [
                'key' => 'operational',
                'title' => 'Pendências operacionais',
                'items' => $this->present($user, $this->actionCenter->baseQuery($user), $limit),
            ],
        ], fn (array $group): bool => $group['items'] !== []));
    }

    /**
     * @param  Builder<WorkTask>  $query
     * @return list<array<string, mixed>>
     */
    private function present(User $user, Builder $query, int $limit): array
    {
        $items = $query
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED])
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 ELSE 1 END")
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
