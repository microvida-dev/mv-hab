<?php

namespace App\Services\Reporting;

use App\Models\Application;
use App\Models\Arrear;
use App\Models\Complaint;
use App\Models\DocumentSubmission;
use App\Models\EligibilityCheck;
use App\Models\HousingUnit;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceRequest;
use App\Services\Reporting\Indicators\ApplicationIndicatorsService;
use App\Services\Reporting\Indicators\FinanceIndicatorsService;
use App\Services\Reporting\Indicators\HousingIndicatorsService;
use App\Services\Reporting\Indicators\MaintenanceIndicatorsService;
use Illuminate\Support\Facades\DB;

class ReportQueryService
{
    public function __construct(
        private readonly ReportFilterService $filters,
        private readonly ApplicationIndicatorsService $applications,
        private readonly HousingIndicatorsService $housing,
        private readonly FinanceIndicatorsService $finance,
        private readonly MaintenanceIndicatorsService $maintenance,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function applicationsByContest(array $filters): array
    {
        return $this->filters->applyApplication(Application::query(), $filters)
            ->join('contests', 'contests.id', '=', 'applications.contest_id')
            ->join('programs', 'programs.id', '=', 'applications.program_id')
            ->select('programs.name as Programa', 'contests.title as Concurso', 'applications.status as Estado', DB::raw('COUNT(*) as Total'))
            ->groupBy('programs.name', 'contests.title', 'applications.status')->orderBy('programs.name')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function applicationStatusSummary(array $filters): array
    {
        return $this->filters->applyApplication(Application::query(), $filters)
            ->select('status as Estado', DB::raw('COUNT(*) as Total'))->groupBy('status')->orderByDesc('Total')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function eligibilitySummary(array $filters): array
    {
        return $this->filters->applyApplication(EligibilityCheck::query(), $filters, 'executed_at')
            ->select('result as Resultado', DB::raw('COUNT(*) as Total'))->groupBy('result')->orderByDesc('Total')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function documentPending(array $filters): array
    {
        return $this->filters->applyThroughApplication(DocumentSubmission::query(), $filters, 'submitted_at')
            ->join('applications', 'applications.id', '=', 'document_submissions.application_id')
            ->leftJoin('document_types', 'document_types.id', '=', 'document_submissions.document_type_id')
            ->whereIn('document_submissions.status', ['missing', 'submitted', 'under_review', 'rejected', 'expired'])
            ->select('applications.application_number as Candidatura', 'document_types.name as Documento', 'document_submissions.status as Estado', 'document_submissions.submitted_at as Submetido_em')
            ->orderBy('applications.application_number')->limit(500)->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function complaintsSummary(array $filters): array
    {
        return $this->filters->applyThroughApplication(Complaint::query(), $filters, 'submitted_at')
            ->select('status as Estado', DB::raw('COUNT(*) as Total'))->groupBy('status')->orderByDesc('Total')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function housingOccupancy(array $filters): array
    {
        return HousingUnit::query()
            ->when($filters['location'] ?? null, fn ($query, $location) => $query->where('address', 'like', '%'.$location.'%'))
            ->select('typology as Tipologia', 'status as Estado', DB::raw('COUNT(*) as Total'))
            ->groupBy('typology', 'status')->orderBy('typology')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function financialArrears(array $filters): array
    {
        return $this->filters->applyThroughContract(Arrear::query(), $filters, 'detected_at')
            ->join('contracts', 'contracts.id', '=', 'arrears.lease_contract_id')
            ->select('contracts.contract_number as Contrato', 'arrears.status as Estado', 'arrears.outstanding_amount as Valor_em_divida', 'arrears.days_overdue as Dias_em_atraso')
            ->whereNotIn('arrears.status', ['regularized', 'closed', 'cancelled'])->orderByDesc('arrears.outstanding_amount')->limit(500)->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function maintenancePending(array $filters): array
    {
        return $this->filters->applyThroughContract(MaintenanceRequest::query(), $filters, 'reported_at')
            ->leftJoin('housing_units', 'housing_units.id', '=', 'maintenance_requests.housing_unit_id')
            ->whereIn('maintenance_requests.status', ['new', 'under_review', 'open', 'scheduled', 'in_progress'])
            ->select('maintenance_requests.request_number as Pedido', 'housing_units.code as Imovel', 'maintenance_requests.status as Estado', 'maintenance_requests.urgency as Urgencia', 'maintenance_requests.reported_at as Registado_em')
            ->orderBy('maintenance_requests.reported_at')->limit(500)->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function maintenanceCostsByProperty(array $filters): array
    {
        return $this->filters->applyThroughContract(MaintenanceCost::query(), $filters, 'registered_at')
            ->join('housing_units', 'housing_units.id', '=', 'maintenance_costs.housing_unit_id')
            ->select('housing_units.code as Imovel', 'housing_units.typology as Tipologia', DB::raw('SUM(maintenance_costs.amount) as Custo_total'))
            ->groupBy('housing_units.code', 'housing_units.typology')->orderByDesc('Custo_total')->get()->map->toArray()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function executiveSummary(array $filters): array
    {
        return [
            ['Indicador' => 'Candidaturas submetidas', 'Valor' => $this->applications->countSubmittedApplications($filters)],
            ['Indicador' => 'Candidaturas elegíveis', 'Valor' => $this->applications->countEligibleApplications($filters)],
            ['Indicador' => 'Tempo médio de análise (dias)', 'Valor' => $this->applications->averageAnalysisTime($filters)],
            ['Indicador' => 'Habitações disponíveis', 'Valor' => $this->housing->countAvailableHousingUnits($filters)],
            ['Indicador' => 'Taxa de ocupação (%)', 'Valor' => $this->housing->occupancyRate($filters)],
            ['Indicador' => 'Renda em atraso (EUR)', 'Valor' => $this->finance->totalOverdueRent($filters)],
            ['Indicador' => 'Pedidos de manutenção pendentes', 'Valor' => $this->maintenance->countPendingRequests($filters)],
            ['Indicador' => 'Custos de manutenção (EUR)', 'Valor' => $this->maintenance->totalMaintenanceCosts($filters)],
        ];
    }
}
