<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricService
{
    public function __construct(private readonly DashboardAuthorizationService $authorization) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        return array_values(array_filter([
            $this->authorizedMetric($user, 'active_users', 'Utilizadores ativos', $this->countRows('users', ['status' => ['active']]), 'Perfis municipais ativos.', 'backoffice.users.index', 'users.view', 'neutral'),
            $this->authorizedMetric($user, 'active_teams', 'Equipas ativas', $this->countRows('municipal_teams', ['status' => ['active']]), 'Equipas municipais disponíveis.', 'backoffice.teams.index', 'teams.view', 'neutral'),
            $this->authorizedMetric($user, 'security_alerts', 'Alertas de segurança', $this->countRows('security_alerts', ['status' => ['open', 'active', 'new']]), 'Alertas operacionais pendentes.', 'backoffice.security.alerts.index', null, 'warning', ['administrator', 'auditor']),
            $this->authorizedMetric($user, 'assigned_tasks', 'Tarefas atribuídas', $this->countWorkTasks($user, assignedOnly: true), 'Trabalho diretamente atribuído ao utilizador.', 'backoffice.work-tasks.my', 'work_tasks.view', 'civic'),
            $this->authorizedMetric($user, 'team_tasks', 'Tarefas da equipa', $this->countWorkTasks($user, teamOnly: true), 'Fila operacional das equipas do utilizador.', 'backoffice.work-tasks.team', 'work_tasks.view_team', 'civic'),
            $this->authorizedMetric($user, 'overdue_tasks', 'Tarefas vencidas', $this->countOverdueWorkTasks($user), 'Itens com SLA ultrapassado.', 'backoffice.work-tasks.overdue', 'work_tasks.view', 'danger'),
            $this->authorizedMetric($user, 'pending_applications', 'Candidaturas pendentes', $this->countRows('applications', ['status' => ['submitted', 'under_review', 'requires_correction', 'correction_submitted']]), 'Candidaturas em análise ou correção.', 'backoffice.applications.index', 'applications.view', 'civic'),
            $this->authorizedMetric($user, 'pending_documents', 'Documentos pendentes', $this->countRows('document_submissions', ['status' => ['missing', 'submitted', 'under_review', 'rejected', 'expired']]), 'Documentos a validar ou corrigir.', 'admin.document-reviews.index', 'documents.view', 'warning'),
            $this->authorizedMetric($user, 'pending_complaints', 'Reclamações pendentes', $this->countRows('complaints', ['status' => ['submitted', 'pending', 'under_review', 'open']]), 'Reclamações/audiência prévia a tratar.', 'backoffice.complaints.index', 'complaints.view', 'warning'),
            $this->authorizedMetric($user, 'pending_contracts', 'Contratos pendentes', $this->countRows('contracts', ['status' => ['draft', 'pending_review', 'pending_signature', 'generated']]), 'Contratos a rever ou formalizar.', 'backoffice.contracts.leases.index', 'contracts.view', 'civic'),
            $this->authorizedMetric($user, 'pending_rents', 'Rendas pendentes', $this->countRows('rent_installments', ['status' => ['pending', 'overdue', 'unpaid']]), 'Rendas manuais por liquidar.', 'backoffice.finance.installments.index', 'finance.view', 'warning'),
            $this->authorizedMetric($user, 'pending_payments', 'Pagamentos por validar', $this->countRows('lease_payments', ['status' => ['pending', 'registered', 'submitted']]), 'Registos financeiros por validação.', 'backoffice.finance.payments.index', 'payments.view', 'warning'),
            $this->authorizedMetric($user, 'open_tickets', 'Tickets abertos', $this->countRows('support_tickets', ['status' => ['open', 'assigned', 'waiting_staff']]), 'Pedidos de apoio em curso.', 'backoffice.support-tickets.index', 'support.view', 'civic'),
            $this->authorizedMetric($user, 'upcoming_visits', 'Visitas agendadas', $this->countUpcoming('housing_visits', 'scheduled_at', ['status' => ['scheduled', 'rescheduled']]), 'Visitas futuras a fogos publicados.', 'backoffice.housing-visits.index', 'visits.view', 'civic'),
            $this->authorizedMetric($user, 'available_housing', 'Fogos disponíveis', $this->countRows('housing_units', ['status' => ['available', 'published']]), 'Oferta habitacional publicável ou livre.', 'housing-units.index', 'housing_units.view', 'neutral'),
            $this->authorizedMetric($user, 'urgent_maintenance', 'Manutenção urgente', $this->countRows('maintenance_requests', ['priority' => ['high', 'urgent'], 'status' => ['open', 'reported', 'in_analysis', 'in_progress', 'scheduled']]), 'Pedidos prioritários por resolver.', 'backoffice.maintenance.index', 'maintenance_requests.view', 'danger'),
            $this->authorizedMetric($user, 'scheduled_inspections', 'Vistorias agendadas', $this->countRows('property_inspections', ['status' => ['scheduled', 'planned', 'in_progress']]), 'Vistorias técnicas em curso.', 'backoffice.inspections.index', 'inspections.view', 'civic'),
            $this->authorizedMetric($user, 'recent_audit_events', 'Eventos de auditoria', $this->countRecentRows('audit_events', 'occurred_at'), 'Eventos críticos/recentes auditáveis.', 'backoffice.security.audit.events.index', 'audit_logs.view', 'neutral'),
            $this->authorizedMetric($user, 'rgpd_requests', 'Pedidos RGPD', $this->countRows('data_subject_requests', ['status' => ['draft', 'pending', 'pending_dpo_approval', 'open']]), 'Pedidos de titular a acompanhar.', 'backoffice.security.privacy.requests.index', 'privacy.view', 'warning'),
        ]));
    }

    /**
     * @param  array<string, list<string>>  $whereIn
     */
    private function countRows(string $table, array $whereIn = []): int
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

    /**
     * @param  array<string, list<string>>  $whereIn
     */
    private function countUpcoming(string $table, string $dateColumn, array $whereIn = []): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return 0;
        }

        $query = DB::table($table)->where($dateColumn, '>=', now());

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

    private function countRecentRows(string $table, string $dateColumn): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where($dateColumn, '>=', now()->subDays(7))
            ->count();
    }

    private function countWorkTasks(User $user, bool $assignedOnly = false, bool $teamOnly = false): int
    {
        if (! Schema::hasTable('work_tasks')) {
            return 0;
        }

        $query = $this->visibleWorkTaskQuery($user)
            ->whereNotIn('status', [WorkTask::STATUS_COMPLETED, WorkTask::STATUS_CANCELLED]);

        if ($assignedOnly) {
            $query->where('assigned_user_id', $user->id);
        }

        if ($teamOnly) {
            $teamIds = $this->activeTeamIds($user);

            if ($teamIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('municipal_team_id', $teamIds);
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
    private function authorizedMetric(
        User $user,
        string $key,
        string $label,
        int $value,
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
            'value' => $value,
            'description' => $description,
            'route' => $route,
            'tone' => $tone,
        ];
    }
}
