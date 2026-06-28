<?php

namespace App\Services\Analytics;

use App\Data\Analytics\WorkloadBucketData;
use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkloadAnalyticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{name: string, team: string, total: int, overdue: int, due_soon: int}>
     */
    public function byResponsible(User $user, array $filters): array
    {
        if (! $user->hasPermission('work_tasks.view') || ! Schema::hasTable('work_tasks')) {
            return [];
        }

        $query = DB::table('work_tasks')
            ->leftJoin('users', 'users.id', '=', 'work_tasks.assigned_user_id')
            ->leftJoin('municipal_teams', 'municipal_teams.id', '=', 'work_tasks.municipal_team_id')
            ->whereNotIn('work_tasks.status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);

        $this->applyFilters($query, $filters);

        $rows = $query
            ->selectRaw("COALESCE(users.name, 'Sem responsável') as user_label")
            ->selectRaw("COALESCE(municipal_teams.name, 'Sem equipa') as team_label")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN work_tasks.status = ? OR work_tasks.due_at < ? THEN 1 ELSE 0 END) as overdue', [WorkTask::STATUS_OVERDUE, now()])
            ->selectRaw('SUM(CASE WHEN work_tasks.due_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as due_soon', [now(), now()->addDays(7)])
            ->groupBy('user_label', 'team_label')
            ->orderByDesc('overdue')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return array_values($rows
            ->map(fn (object $row): array => (new WorkloadBucketData(
                (string) $row->user_label,
                (string) $row->team_label,
                (int) $row->total,
                (int) $row->overdue,
                (int) $row->due_soon,
            ))->toArray())
            ->all());
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
    }
}
