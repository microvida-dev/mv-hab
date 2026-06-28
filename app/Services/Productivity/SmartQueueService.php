<?php

namespace App\Services\Productivity;

use App\Models\User;
use App\Models\WorkTask;

class SmartQueueService
{
    public function __construct(
        private readonly SmartActionCenterService $actionCenter,
        private readonly ProductivityPresenter $presenter,
    ) {}

    /**
     * @return list<array{key: string, title: string, criteria: string, items: list<array<string, mixed>>}>
     */
    public function forUser(User $user, int $limit = 6): array
    {
        return array_values(array_filter([
            [
                'key' => 'urgent',
                'title' => 'Urgente',
                'criteria' => 'Prioridade urgente/alta e estado ativo.',
                'items' => $this->items($user, fn ($query) => $query->whereIn('priority', [
                    WorkTask::PRIORITY_URGENT,
                    WorkTask::PRIORITY_HIGH,
                ]), $limit),
            ],
            [
                'key' => 'today',
                'title' => 'Hoje',
                'criteria' => 'Prazo definido para hoje.',
                'items' => $this->items($user, fn ($query) => $query->whereDate('due_at', today()), $limit),
            ],
            [
                'key' => 'week',
                'title' => 'Esta semana',
                'criteria' => 'Prazo até ao fim da semana.',
                'items' => $this->items($user, fn ($query) => $query->whereBetween('due_at', [now(), now()->endOfWeek()]), $limit),
            ],
            [
                'key' => 'unassigned',
                'title' => 'Sem responsável',
                'criteria' => 'Tarefas ativas sem técnico atribuído.',
                'items' => $this->items($user, fn ($query) => $query->whereNull('assigned_user_id'), $limit),
            ],
            [
                'key' => 'blocked',
                'title' => 'Bloqueados',
                'criteria' => 'Estados de espera existentes.',
                'items' => $this->items($user, fn ($query) => $query->whereIn('status', [
                    WorkTask::STATUS_WAITING_CANDIDATE,
                    WorkTask::STATUS_WAITING_INTERNAL,
                    WorkTask::STATUS_WAITING_EXTERNAL,
                ]), $limit),
            ],
            [
                'key' => 'overdue',
                'title' => 'Em atraso',
                'criteria' => 'Estado vencido ou prazo ultrapassado.',
                'items' => $this->actionCenter->overdue($user, $limit),
            ],
        ], fn (array $queue): bool => $queue['items'] !== []));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(User $user, callable $callback, int $limit): array
    {
        $query = $this->actionCenter->baseQuery($user)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);

        $callback($query);

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
