<?php

namespace App\Services\Analytics;

use App\Data\Analytics\AnalyticsMetricData;
use App\Models\User;
use App\Services\Reports\MunicipalKpiService;
use Illuminate\Support\Facades\Route;

class MetricAggregationService
{
    public function __construct(private readonly MunicipalKpiService $kpis) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function snapshot(array $filters): array
    {
        return $this->kpis->snapshot($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array{title: string, value: string, period: string, description: string, tone: string, href: string|null, trend_label: string|null}>
     */
    public function executiveMetrics(User $user, array $filters): array
    {
        $snapshot = $this->snapshot($filters);
        $values = is_array($snapshot['kpis'] ?? null) ? $snapshot['kpis'] : [];

        $metrics = [
            new AnalyticsMetricData(
                'Candidaturas recebidas',
                (string) array_sum($this->integerMap($values['applications_by_status'] ?? [])),
                'Período filtrado',
                'Volume agregado de candidaturas registadas.',
                'info',
                $this->href($user, 'backoffice.applications.index', 'applications.view'),
            ),
            new AnalyticsMetricData(
                'Documentos por validar',
                (string) (int) ($values['pending_documents'] ?? 0),
                'Estado atual',
                'Submissões documentais pendentes de validação técnica.',
                ((int) ($values['pending_documents'] ?? 0)) > 0 ? 'warning' : 'success',
                $this->href($user, 'admin.document-reviews.index', 'documents.view'),
            ),
            new AnalyticsMetricData(
                'Tarefas em atraso',
                (string) (int) (($values['work_tasks_by_sla']['overdue'] ?? 0)),
                'SLA operacional',
                'Tarefas ativas com prazo ultrapassado ou marcadas como vencidas.',
                ((int) (($values['work_tasks_by_sla']['overdue'] ?? 0))) > 0 ? 'overdue' : 'success',
                $this->href($user, 'backoffice.work-tasks.overdue', 'work_tasks.view'),
            ),
            new AnalyticsMetricData(
                'Concursos ativos',
                (string) (int) ($values['active_contests'] ?? 0),
                'Oferta municipal',
                'Concursos publicados ou ativos no período em análise.',
                'civic',
                $this->href($user, 'admin.contests.index', 'contests.view'),
            ),
            new AnalyticsMetricData(
                'Tickets abertos',
                (string) array_sum($this->onlyKeys($values['tickets_by_status'] ?? [], ['open', 'assigned', 'waiting_staff', 'waiting_candidate'])),
                'Atendimento',
                'Pedidos de apoio ainda não resolvidos ou encerrados.',
                'pending',
                $this->href($user, 'backoffice.support-tickets.index', 'support.view'),
            ),
        ];

        if ($user->hasPermission('contracts.view')) {
            $metrics[] = new AnalyticsMetricData(
                'Contratos ativos',
                (string) (int) ($values['active_contracts'] ?? 0),
                'Pós-atribuição',
                'Contratos ativos em gestão municipal.',
                'success',
                $this->href($user, 'backoffice.contracts.leases.index', 'contracts.view'),
            );
        }

        if ($user->hasPermission('reports.view_financial') || $user->hasPermission('finance.view')) {
            $metrics[] = new AnalyticsMetricData(
                'Rendas em aberto',
                (string) array_sum($this->onlyKeys($values['rents_by_status'] ?? [], ['pending', 'overdue', 'open'])),
                'Gestão manual',
                'Indicador agregado de rendas manuais com estado pendente.',
                'warning',
                $this->href($user, 'backoffice.finance.accounts.index', 'finance.view'),
            );
        }

        if ($user->hasPermission('privacy.view')) {
            $metrics[] = new AnalyticsMetricData(
                'Pedidos RGPD',
                (string) array_sum($this->integerMap($values['rgpd_requests_by_status'] ?? [])),
                'Conformidade',
                'Pedidos do titular agregados por estado.',
                'info',
                $this->href($user, 'backoffice.security.privacy.requests.index', 'privacy.view'),
            );
        }

        return array_map(
            fn (AnalyticsMetricData $metric): array => $metric->toArray(),
            $metrics,
        );
    }

    /**
     * @return array<string, int>
     */
    private function integerMap(mixed $map): array
    {
        if (! is_array($map)) {
            return [];
        }

        return collect($map)
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    private function onlyKeys(mixed $map, array $keys): array
    {
        return collect($this->integerMap($map))
            ->only($keys)
            ->all();
    }

    private function href(User $user, string $routeName, string $permission): ?string
    {
        if (! Route::has($routeName) || ! $user->hasPermission($permission)) {
            return null;
        }

        return route($routeName);
    }
}
