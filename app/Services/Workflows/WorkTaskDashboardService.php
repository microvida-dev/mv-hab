<?php

namespace App\Services\Workflows;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WorkTaskDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(User $actor): array
    {
        $query = $this->visibleQuery($actor);

        return [
            'by_status' => (clone $query)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'overdue' => (clone $query)->where('status', WorkTask::STATUS_OVERDUE)->count(),
            'due_soon' => (clone $query)
                ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED, WorkTask::STATUS_OVERDUE])
                ->whereBetween('due_at', [now(), now()->addDays(2)])
                ->count(),
            'completed_last_30_days' => (clone $query)
                ->where('status', WorkTask::STATUS_COMPLETED)
                ->where('completed_at', '>=', now()->subDays(30))
                ->count(),
            'team_load' => $this->teamLoad($actor),
            'user_load' => $this->userLoad($actor),
            'sla_rate' => $this->slaComplianceRate($actor),
        ];
    }

    /** @return Builder<WorkTask> */
    public function visibleQuery(User $actor): Builder
    {
        $query = WorkTask::query()->with(['municipalTeam', 'assignedUser']);

        if ($actor->hasPermission('work_tasks.assign') || $actor->hasRole(['administrator', 'auditor'])) {
            return $query;
        }

        if ($actor->hasPermission('work_tasks.view_team')) {
            $teamIds = $actor->municipalTeams()
                ->wherePivotNull('left_at')
                ->pluck('municipal_teams.id');

            return $query->where(function ($inner) use ($actor, $teamIds): void {
                $inner->where('assigned_user_id', $actor->id)
                    ->orWhereIn('municipal_team_id', $teamIds);
            });
        }

        return $query->where('assigned_user_id', $actor->id);
    }

    /**
     * @return Collection<int, WorkTask>
     */
    public function teamLoad(User $actor): Collection
    {
        return $this->visibleQuery($actor)
            ->selectRaw('municipal_team_id, status, count(*) as total')
            ->groupBy('municipal_team_id', 'status')
            ->orderBy('municipal_team_id')
            ->get();
    }

    /**
     * @return Collection<int, WorkTask>
     */
    public function userLoad(User $actor): Collection
    {
        return $this->visibleQuery($actor)
            ->selectRaw('assigned_user_id, status, count(*) as total')
            ->groupBy('assigned_user_id', 'status')
            ->orderBy('assigned_user_id')
            ->get();
    }

    private function slaComplianceRate(User $actor): float
    {
        $completed = $this->visibleQuery($actor)
            ->where('status', WorkTask::STATUS_COMPLETED)
            ->whereNotNull('completed_at');

        $total = (clone $completed)->count();

        if ($total === 0) {
            return 100.0;
        }

        $withinSla = (clone $completed)
            ->whereColumn('completed_at', '<=', 'due_at')
            ->count();

        return round(($withinSla / $total) * 100, 2);
    }
}
