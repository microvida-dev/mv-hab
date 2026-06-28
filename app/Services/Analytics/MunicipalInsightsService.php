<?php

namespace App\Services\Analytics;

use App\Data\Analytics\ExecutiveSummaryData;

class MunicipalInsightsService
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{title: string, description: string, highlights: list<string>, warnings: list<string>}
     */
    public function executiveSummary(array $snapshot): array
    {
        $kpis = is_array($snapshot['kpis'] ?? null) ? $snapshot['kpis'] : [];
        $applications = is_array($kpis['applications_by_status'] ?? null) ? array_sum($kpis['applications_by_status']) : 0;
        $documents = (int) ($kpis['pending_documents'] ?? 0);
        $overdueTasks = (int) ($kpis['work_tasks_by_sla']['overdue'] ?? 0);

        $highlights = [
            'Operação agregada sem exposição de dados pessoais.',
            'Leitura executiva baseada em contagens e distribuições.',
            'Funil municipal apresentado apenas como visualização operacional.',
        ];

        if ($applications > 0) {
            $highlights[] = $applications.' candidaturas agregadas no contexto filtrado.';
        }

        $warnings = [];
        if ($documents > 0) {
            $warnings[] = $documents.' documentos aguardam validação técnica.';
        }

        if ($overdueTasks > 0) {
            $warnings[] = $overdueTasks.' tarefas estão em atraso operacional.';
        }

        return (new ExecutiveSummaryData(
            'Leitura executiva municipal',
            'Resumo automático de métricas agregadas para apoio à coordenação municipal.',
            $highlights,
            $warnings,
        ))->toArray();
    }
}
