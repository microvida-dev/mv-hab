<?php

namespace App\Services\Reporting;

use App\Models\ReportDefinition;
use InvalidArgumentException;

class ReportQueryRegistry
{
    private const ALLOWED = [
        ReportQueryService::class => [
            'applicationsByContest',
            'applicationStatusSummary',
            'eligibilitySummary',
            'documentPending',
            'complaintsSummary',
            'housingOccupancy',
            'financialArrears',
            'maintenancePending',
            'maintenanceCostsByProperty',
            'executiveSummary',
        ],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function execute(ReportDefinition $definition, array $filters): array
    {
        if (! in_array($definition->query_method, self::ALLOWED[$definition->query_service] ?? [], true)) {
            throw new InvalidArgumentException('O relatório não referencia uma consulta permitida.');
        }

        return app($definition->query_service)->{$definition->query_method}($filters);
    }
}
