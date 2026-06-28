<?php

namespace App\Services\Analytics;

use App\Data\Analytics\SlaBucketData;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SlaAnalyticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{buckets: list<array{label: string, value: int, status: string, description: string}>, compliance_rate: int}
     */
    public function summary(User $user, array $filters): array
    {
        if (! $user->hasPermission('work_tasks.view') || ! Schema::hasTable('work_tasks')) {
            return ['buckets' => [], 'compliance_rate' => 0];
        }

        $base = DB::table('work_tasks');
        $this->applyFilters($base, $filters);

        $active = (clone $base)->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);
        $completed = (int) (clone $base)->where('status', WorkTask::STATUS_COMPLETED)->count();
        $overdue = (int) (clone $active)
            ->where(function ($query): void {
                $query->where('status', WorkTask::STATUS_OVERDUE)
                    ->orWhere('due_at', '<', now());
            })
            ->count();
        $dueSoon = (int) (clone $active)->whereBetween('due_at', [now(), now()->addDays(7)])->count();
        $withoutDueDate = (int) (clone $active)->whereNull('due_at')->count();
        $onTrack = max((int) (clone $active)->whereNotNull('due_at')->count() - $overdue - $dueSoon, 0);
        $totalClosed = $completed + $overdue;

        $buckets = [
            new SlaBucketData('Dentro do prazo', $onTrack, 'success', 'Tarefas ativas com prazo definido e sem alerta.'),
            new SlaBucketData('A vencer', $dueSoon, 'warning', 'Tarefas com prazo nos próximos sete dias.'),
            new SlaBucketData('Em atraso', $overdue, 'overdue', 'Tarefas vencidas ou marcadas como em atraso.'),
            new SlaBucketData('Sem prazo', $withoutDueDate, 'neutral', 'Tarefas ativas sem data de SLA definida.'),
            new SlaBucketData('Concluído', $completed, 'completed', 'Tarefas concluídas no período filtrado.'),
        ];

        return [
            'buckets' => array_map(fn (SlaBucketData $bucket): array => $bucket->toArray(), $buckets),
            'compliance_rate' => $totalClosed > 0 ? (int) round(($completed / $totalClosed) * 100) : 0,
        ];
    }

    /**
     * @param  Builder  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        foreach (['municipal_team_id', 'assigned_user_id', 'status', 'priority'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn('work_tasks', $column)) {
                $query->where('work_tasks.'.$column, $filters[$column]);
            }
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('work_tasks.created_at', '>=', (string) $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('work_tasks.created_at', '<=', (string) $filters['date_to']);
        }
    }
}
