<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDeadlineService
{
    public function __construct(private readonly DashboardAuthorizationService $authorization) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        return array_values(array_filter([
            $this->authorizedAlert($user, 'overdue_tasks', 'Tarefas vencidas', $this->countOverdueWorkTasks($user), 'SLA ultrapassado; requer triagem.', 'backoffice.work-tasks.overdue', 'work_tasks.view', 'danger'),
            $this->authorizedAlert($user, 'due_soon_tasks', 'Tarefas a vencer', $this->countDueSoonWorkTasks($user), 'Prazo nas próximas 48 horas.', 'backoffice.work-tasks.my', 'work_tasks.view', 'warning'),
            $this->authorizedAlert($user, 'pending_documents', 'Documentos pendentes', $this->countRows('document_submissions', ['status' => ['submitted', 'under_review', 'missing', 'rejected']]), 'Validação documental por concluir.', 'admin.document-reviews.index', 'documents.view', 'warning'),
            $this->authorizedAlert($user, 'pending_complaints', 'Reclamações pendentes', $this->countRows('complaints', ['status' => ['submitted', 'pending', 'under_review', 'open']]), 'Reclamações ou audiência prévia em aberto.', 'backoffice.complaints.index', 'complaints.view', 'warning'),
            $this->authorizedAlert($user, 'pending_contracts', 'Contratos pendentes', $this->countRows('contracts', ['status' => ['draft', 'pending_review', 'pending_signature', 'generated']]), 'Contratos a rever/formalizar.', 'backoffice.contracts.leases.index', 'contracts.view', 'civic'),
            $this->authorizedAlert($user, 'rgpd_requests', 'Pedidos RGPD', $this->countRows('data_subject_requests', ['status' => ['draft', 'pending', 'pending_dpo_approval', 'open']]), 'Pedidos de titular em aberto.', 'backoffice.security.privacy.requests.index', 'privacy.view', 'warning'),
            $this->authorizedAlert($user, 'security_alerts', 'Alertas de segurança', $this->countRows('security_alerts', ['status' => ['open', 'active', 'new']]), 'Riscos técnicos pendentes.', 'backoffice.security.alerts.index', null, 'danger', ['administrator', 'auditor']),
        ]));
    }

    /**
     * @param  array<string, list<string>>  $whereIn
     */
    private function countRows(string $table, array $whereIn): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        foreach ($whereIn as $column => $values) {
            if (Schema::hasColumn($table, $column)) {
                $query->whereIn($column, $values);
            }
        }

        return (int) $query->count();
    }

    private function countOverdueWorkTasks(User $user): int
    {
        if (! Schema::hasTable('work_tasks')) {
            return 0;
        }

        return (int) $this->visibleWorkTaskQuery($user)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED])
            ->where(function (Builder $query): void {
                $query->where('status', WorkTask::STATUS_OVERDUE)
                    ->orWhere('due_at', '<', now());
            })
            ->count();
    }

    private function countDueSoonWorkTasks(User $user): int
    {
        if (! Schema::hasTable('work_tasks')) {
            return 0;
        }

        return (int) $this->visibleWorkTaskQuery($user)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED, WorkTask::STATUS_OVERDUE])
            ->whereBetween('due_at', [now(), now()->addDays(2)])
            ->count();
    }

    private function visibleWorkTaskQuery(User $user): Builder
    {
        $query = DB::table('work_tasks');

        if (Schema::hasColumn('work_tasks', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($this->authorization->hasAnyRole($user, ['administrator', 'auditor']) || $this->authorization->hasPermission($user, 'work_tasks.assign')) {
            return $query;
        }

        if ($this->authorization->hasPermission($user, 'work_tasks.view_team')) {
            $teamIds = $this->activeTeamIds($user);

            return $query->where(function (Builder $inner) use ($user, $teamIds): void {
                $inner->where('assigned_user_id', $user->id);

                if ($teamIds !== []) {
                    $inner->orWhereIn('municipal_team_id', $teamIds);
                }
            });
        }

        return $query->where('assigned_user_id', $user->id);
    }

    /**
     * @return array<int, int>
     */
    private function activeTeamIds(User $user): array
    {
        return $user->municipalTeams()
            ->wherePivotNull('left_at')
            ->pluck('municipal_teams.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<string>|null  $roles
     * @return array<string, mixed>|null
     */
    private function authorizedAlert(
        User $user,
        string $key,
        string $label,
        int $count,
        string $description,
        string $route,
        ?string $permission,
        string $tone,
        ?array $roles = null,
    ): ?array {
        $item = array_filter([
            'route' => $route,
            'permission' => $permission,
            'roles' => $roles,
        ], fn (mixed $candidate): bool => $candidate !== null);

        if (! $this->authorization->canSeeItem($user, $item)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'description' => $description,
            'route' => $route,
            'tone' => $tone,
        ];
    }
}
