<?php

namespace App\Services\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MunicipalKpiService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function snapshot(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'generated_at' => now()->toIso8601String(),
            'filters' => $filters,
            'kpis' => [
                'active_contests' => $this->countRows('contests', $filters, 'created_at', ['status' => ['published']]),
                'applications_by_status' => $this->groupCount('applications', 'status', $filters),
                'applications_by_typology' => $this->applicationsByHousingField('current_housing_typology', $filters),
                'applications_by_parish' => $this->applicationsByHousingField('current_parish', $filters),
                'pending_documents' => $this->countRows('document_submissions', $filters, 'submitted_at', ['status' => ['missing', 'submitted', 'under_review', 'rejected', 'expired']]),
                'correction_requests' => $this->groupCount('correction_requests', 'status', $filters, 'created_at'),
                'visits_by_status' => $this->groupCount('housing_visits', 'status', $filters, 'created_at'),
                'tickets_by_status' => $this->groupCount('support_tickets', 'status', $filters, 'created_at'),
                'work_tasks_by_team' => $this->workTasksByTeam($filters),
                'work_tasks_by_sla' => $this->workTasksBySla($filters),
                'lists_by_type' => $this->groupCount('list_publications', 'publication_type', $filters, 'published_at'),
                'active_contracts' => $this->countRows('contracts', $filters, 'created_at', ['status' => ['active']]),
                'rents_by_status' => $this->groupCount('rent_installments', 'status', $filters, 'due_date'),
                'maintenance_by_status' => $this->groupCount('maintenance_requests', 'status', $filters, 'reported_at'),
                'inspections_by_status' => $this->groupCount('property_inspections', 'status', $filters, 'scheduled_for'),
                'rgpd_requests_by_status' => $this->groupCount('data_subject_requests', 'status', $filters, 'received_at'),
                'critical_audit_events' => $this->criticalAuditEvents($filters),
            ],
        ];
    }

    /**
     * @return list<array{code: string, label: string, sensitivity: string, source: string}>
     */
    public function catalog(): array
    {
        return [
            ['code' => 'active_contests', 'label' => 'Concursos ativos/publicados', 'sensitivity' => 'internal', 'source' => 'contests'],
            ['code' => 'applications_by_status', 'label' => 'Candidaturas por estado', 'sensitivity' => 'restricted', 'source' => 'applications'],
            ['code' => 'applications_by_typology', 'label' => 'Candidaturas por tipologia', 'sensitivity' => 'aggregated', 'source' => 'applications/current_housing_situations'],
            ['code' => 'applications_by_parish', 'label' => 'Candidaturas por freguesia', 'sensitivity' => 'aggregated', 'source' => 'applications/current_housing_situations'],
            ['code' => 'pending_documents', 'label' => 'Documentos pendentes', 'sensitivity' => 'sensitive_aggregated', 'source' => 'document_submissions'],
            ['code' => 'correction_requests', 'label' => 'Pedidos de aperfeiçoamento', 'sensitivity' => 'restricted', 'source' => 'correction_requests'],
            ['code' => 'visits_by_status', 'label' => 'Visitas por estado', 'sensitivity' => 'aggregated', 'source' => 'housing_visits'],
            ['code' => 'tickets_by_status', 'label' => 'Tickets por estado', 'sensitivity' => 'aggregated', 'source' => 'support_tickets'],
            ['code' => 'work_tasks_by_team', 'label' => 'Tarefas por equipa', 'sensitivity' => 'internal', 'source' => 'work_tasks'],
            ['code' => 'work_tasks_by_sla', 'label' => 'Cumprimento SLA operacional', 'sensitivity' => 'internal', 'source' => 'work_tasks'],
            ['code' => 'lists_by_type', 'label' => 'Listas publicadas por tipo', 'sensitivity' => 'internal', 'source' => 'list_publications'],
            ['code' => 'active_contracts', 'label' => 'Contratos ativos', 'sensitivity' => 'restricted', 'source' => 'contracts'],
            ['code' => 'rents_by_status', 'label' => 'Rendas manuais por estado', 'sensitivity' => 'financial_aggregated', 'source' => 'rent_installments'],
            ['code' => 'maintenance_by_status', 'label' => 'Manutenções por estado', 'sensitivity' => 'aggregated', 'source' => 'maintenance_requests'],
            ['code' => 'inspections_by_status', 'label' => 'Vistorias por estado', 'sensitivity' => 'aggregated', 'source' => 'property_inspections'],
            ['code' => 'rgpd_requests_by_status', 'label' => 'Pedidos RGPD por estado', 'sensitivity' => 'rgpd_aggregated', 'source' => 'data_subject_requests'],
            ['code' => 'critical_audit_events', 'label' => 'Ações críticas de auditoria', 'sensitivity' => 'audit_aggregated', 'source' => 'audit_events'],
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedFilters(): array
    {
        return ['program_id', 'contest_id', 'status', 'typology', 'parish', 'date_from', 'date_to', 'municipal_team_id', 'assigned_user_id', 'sla'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        return collect($filters)
            ->only($this->allowedFilters())
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, list<string>>  $whereIn
     */
    private function countRows(string $table, array $filters, ?string $dateColumn = 'created_at', array $whereIn = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        $this->applyStandardFilters($query, $table, $filters, $dateColumn);

        foreach ($whereIn as $column => $values) {
            if (Schema::hasColumn($table, $column)) {
                $query->whereIn($column, $values);
            }
        }

        return (int) $query->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function groupCount(string $table, string $column, array $filters, ?string $dateColumn = 'created_at'): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return [];
        }

        $query = DB::table($table);
        $this->applyStandardFilters($query, $table, $filters, $dateColumn);

        return $query
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderBy($column)
            ->pluck('total', $column)
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function applicationsByHousingField(string $field, array $filters): array
    {
        if (
            ! Schema::hasTable('applications')
            || ! Schema::hasTable('current_housing_situations')
            || ! Schema::hasColumn('applications', 'current_housing_situation_id')
            || ! Schema::hasColumn('current_housing_situations', $field)
        ) {
            return [];
        }

        $query = DB::table('applications')
            ->join('current_housing_situations', 'current_housing_situations.id', '=', 'applications.current_housing_situation_id')
            ->whereNotNull('current_housing_situations.'.$field);

        $this->applyStandardFilters($query, 'applications', $filters, 'created_at');

        return $query
            ->select('current_housing_situations.'.$field.' as label', DB::raw('COUNT(*) as total'))
            ->groupBy('current_housing_situations.'.$field)
            ->orderBy('label')
            ->pluck('total', 'label')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function workTasksByTeam(array $filters): array
    {
        if (! Schema::hasTable('work_tasks')) {
            return [];
        }

        $query = DB::table('work_tasks')
            ->leftJoin('municipal_teams', 'municipal_teams.id', '=', 'work_tasks.municipal_team_id');

        $this->applyStandardFilters($query, 'work_tasks', $filters, 'created_at');

        return $query
            ->selectRaw("COALESCE(municipal_teams.name, 'Sem equipa') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderBy('label')
            ->pluck('total', 'label')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{overdue: int, due_soon: int, active_with_due_date: int}
     */
    private function workTasksBySla(array $filters): array
    {
        if (! Schema::hasTable('work_tasks')) {
            return ['overdue' => 0, 'due_soon' => 0, 'active_with_due_date' => 0];
        }

        $base = DB::table('work_tasks');
        $this->applyStandardFilters($base, 'work_tasks', $filters, 'created_at');
        $base->whereNotIn('status', ['completed', 'cancelled']);

        return [
            'overdue' => (int) (clone $base)->where(function (Builder $query): void {
                $query->where('status', 'overdue')->orWhere('due_at', '<', now());
            })->count(),
            'due_soon' => (int) (clone $base)->whereBetween('due_at', [now(), now()->addDays(2)])->count(),
            'active_with_due_date' => (int) (clone $base)->whereNotNull('due_at')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function criticalAuditEvents(array $filters): array
    {
        if (! Schema::hasTable('audit_events')) {
            return [];
        }

        $query = DB::table('audit_events');
        $this->applyStandardFilters($query, 'audit_events', $filters, 'occurred_at');

        if (Schema::hasColumn('audit_events', 'severity')) {
            $query->whereIn('severity', ['warning', 'error', 'critical']);
        }

        return $query
            ->select('event_category', DB::raw('COUNT(*) as total'))
            ->groupBy('event_category')
            ->orderBy('event_category')
            ->pluck('total', 'event_category')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyStandardFilters(Builder $query, string $table, array $filters, ?string $dateColumn): void
    {
        foreach (['program_id', 'contest_id', 'status', 'municipal_team_id', 'assigned_user_id'] as $column) {
            if (isset($filters[$column]) && Schema::hasColumn($table, $column)) {
                $query->where($table.'.'.$column, $filters[$column]);
            }
        }

        if (isset($filters['typology']) && Schema::hasColumn($table, 'typology')) {
            $query->where($table.'.typology', $filters['typology']);
        }

        if (isset($filters['parish']) && Schema::hasColumn($table, 'parish')) {
            $query->where($table.'.parish', $filters['parish']);
        }

        if ($dateColumn !== null && Schema::hasColumn($table, $dateColumn)) {
            if (isset($filters['date_from'])) {
                $query->whereDate($table.'.'.$dateColumn, '>=', (string) $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->whereDate($table.'.'.$dateColumn, '<=', (string) $filters['date_to']);
            }
        }
    }
}
