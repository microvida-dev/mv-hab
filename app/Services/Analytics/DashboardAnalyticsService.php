<?php

namespace App\Services\Analytics;

use App\Models\User;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly MetricAggregationService $metrics,
        private readonly ChartDatasetService $charts,
        private readonly TrendAnalysisService $trends,
        private readonly FunnelAnalysisService $funnel,
        private readonly TerritorialDistributionService $territorial,
        private readonly SlaAnalyticsService $sla,
        private readonly WorkloadAnalyticsService $workload,
        private readonly OperationalStatisticsService $statistics,
        private readonly MunicipalInsightsService $insights,
        private readonly ProfileAnalyticsService $profiles,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function forUser(User $user, array $filters): array
    {
        $snapshot = $this->metrics->snapshot($filters);
        $kpis = is_array($snapshot['kpis'] ?? null) ? $snapshot['kpis'] : [];

        return [
            'generated_at' => now(),
            'profile' => $this->profiles->resolve($user),
            'summary' => $this->insights->executiveSummary($snapshot),
            'kpis' => $this->metrics->executiveMetrics($user, $filters),
            'trends' => [
                $this->trends->monthlyApplications($filters),
                $this->trends->monthlyWorkTasks($filters),
            ],
            'charts' => [
                $this->charts->fromKeyedCounts('bar', 'Candidaturas por estado', 'Distribuição agregada dos estados das candidaturas.', $this->integerMap($kpis['applications_by_status'] ?? [])),
                $this->charts->fromKeyedCounts('donut', 'Documentos e validação', 'Estados agregados dos documentos quando disponíveis.', $this->documentStatusMap($filters)),
                $this->charts->fromKeyedCounts('bar', 'Tickets por estado', 'Pedidos de apoio agregados por estado.', $this->integerMap($kpis['tickets_by_status'] ?? [])),
                $this->territorial->applicationsByParish($filters),
            ],
            'funnel' => $this->funnel->municipalFlow($filters),
            'sla' => $this->sla->summary($user, $filters),
            'workload' => $this->workload->byResponsible($user, $filters),
            'tables' => [
                'applications_by_contest' => $this->statistics->applicationsByContest($filters),
                'operations' => $this->statistics->operationsTable($filters),
            ],
            'omitted_metrics' => $this->omittedMetrics($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function executive(User $user, array $filters): array
    {
        $analytics = $this->forUser($user, $filters);

        return [
            'summary' => $analytics['summary'],
            'kpis' => $analytics['kpis'],
            'trends' => $analytics['trends'],
            'funnel' => $analytics['funnel'],
            'sla' => $analytics['sla'],
            'workload' => $analytics['workload'],
        ];
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
            ->mapWithKeys(fn (mixed $value, string|int $key): array => [(string) $key => (int) $value])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function documentStatusMap(array $filters): array
    {
        $snapshot = $this->metrics->snapshot($filters);
        $pending = (int) (($snapshot['kpis']['pending_documents'] ?? 0));

        return [
            'pendentes' => $pending,
            'tratados' => 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function omittedMetrics(User $user): array
    {
        $omitted = [
            'Métricas nominais de cidadãos omitidas por minimização RGPD.',
            'Documentos privados e caminhos de storage omitidos.',
        ];

        if (! $user->hasPermission('reports.view_financial') && ! $user->hasPermission('finance.view')) {
            $omitted[] = 'Métricas financeiras detalhadas omitidas por falta de permissão.';
        }

        return $omitted;
    }
}
