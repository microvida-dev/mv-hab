<?php

namespace Database\Seeders;

use App\Enums\IndicatorCategory;
use App\Enums\IndicatorValueType;
use App\Models\IndicatorDefinition;
use App\Services\Reporting\Indicators\AllocationIndicatorsService;
use App\Services\Reporting\Indicators\ApplicationIndicatorsService;
use App\Services\Reporting\Indicators\CommunicationIndicatorsService;
use App\Services\Reporting\Indicators\ComplaintIndicatorsService;
use App\Services\Reporting\Indicators\DocumentIndicatorsService;
use App\Services\Reporting\Indicators\FinanceIndicatorsService;
use App\Services\Reporting\Indicators\HousingIndicatorsService;
use App\Services\Reporting\Indicators\MaintenanceIndicatorsService;
use Illuminate\Database\Seeder;

class IndicatorDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['applications_submitted', 'Candidaturas submetidas', IndicatorCategory::Applications, IndicatorValueType::Count, ApplicationIndicatorsService::class, 'countSubmittedApplications', null, false],
            ['applications_eligible', 'Candidaturas elegíveis', IndicatorCategory::Eligibility, IndicatorValueType::Count, ApplicationIndicatorsService::class, 'countEligibleApplications', null, false],
            ['applications_excluded', 'Candidaturas excluídas', IndicatorCategory::Eligibility, IndicatorValueType::Count, ApplicationIndicatorsService::class, 'countExcludedApplications', null, false],
            ['applications_average_analysis_days', 'Tempo médio de análise', IndicatorCategory::Applications, IndicatorValueType::Days, ApplicationIndicatorsService::class, 'averageAnalysisTime', null, false],
            ['documents_pending', 'Documentos pendentes', IndicatorCategory::Documents, IndicatorValueType::Count, DocumentIndicatorsService::class, 'countPendingDocuments', 'reports.view_sensitive', true],
            ['documents_rejected', 'Documentos rejeitados', IndicatorCategory::Documents, IndicatorValueType::Count, DocumentIndicatorsService::class, 'countRejectedDocuments', 'reports.view_sensitive', true],
            ['applications_incomplete_documents', 'Candidaturas com documentação incompleta', IndicatorCategory::Documents, IndicatorValueType::Count, DocumentIndicatorsService::class, 'countIncompleteApplications', 'reports.view_sensitive', true],
            ['complaints_pending', 'Reclamações em análise', IndicatorCategory::Complaints, IndicatorValueType::Count, ComplaintIndicatorsService::class, 'countComplaintsUnderReview', null, false],
            ['hearings_pending', 'Audiências pendentes', IndicatorCategory::Complaints, IndicatorValueType::Count, ComplaintIndicatorsService::class, 'countPendingHearings', null, false],
            ['housing_available', 'Habitações disponíveis', IndicatorCategory::Housing, IndicatorValueType::Count, HousingIndicatorsService::class, 'countAvailableHousingUnits', null, false],
            ['housing_allocated', 'Habitações atribuídas', IndicatorCategory::Housing, IndicatorValueType::Count, HousingIndicatorsService::class, 'countAllocatedHousingUnits', null, false],
            ['housing_occupancy_rate', 'Taxa de ocupação', IndicatorCategory::Housing, IndicatorValueType::Percentage, HousingIndicatorsService::class, 'occupancyRate', null, false],
            ['allocations_pending_response', 'Respostas de atribuição pendentes', IndicatorCategory::Allocation, IndicatorValueType::Count, AllocationIndicatorsService::class, 'countPendingAllocationResponses', null, false],
            ['allocation_rate', 'Taxa de atribuição', IndicatorCategory::Allocation, IndicatorValueType::Percentage, AllocationIndicatorsService::class, 'allocationRate', null, false],
            ['finance_overdue_amount', 'Renda em atraso', IndicatorCategory::Finance, IndicatorValueType::Currency, FinanceIndicatorsService::class, 'totalOverdueRent', 'reports.view_financial', true],
            ['finance_contracts_in_arrears', 'Contratos em incumprimento', IndicatorCategory::Finance, IndicatorValueType::Count, FinanceIndicatorsService::class, 'countContractsInArrears', 'reports.view_financial', true],
            ['finance_pending_reviews', 'Revisões de renda pendentes', IndicatorCategory::Finance, IndicatorValueType::Count, FinanceIndicatorsService::class, 'countPendingRentReviews', 'reports.view_financial', true],
            ['maintenance_pending', 'Pedidos de manutenção pendentes', IndicatorCategory::Maintenance, IndicatorValueType::Count, MaintenanceIndicatorsService::class, 'countPendingRequests', 'reports.view_maintenance', false],
            ['maintenance_average_resolution_days', 'Tempo médio de resolução', IndicatorCategory::Maintenance, IndicatorValueType::Days, MaintenanceIndicatorsService::class, 'averageResolutionTime', 'reports.view_maintenance', false],
            ['maintenance_total_costs', 'Custos de manutenção', IndicatorCategory::Maintenance, IndicatorValueType::Currency, MaintenanceIndicatorsService::class, 'totalMaintenanceCosts', 'reports.view_maintenance', true],
            ['inspections_scheduled', 'Vistorias agendadas', IndicatorCategory::Maintenance, IndicatorValueType::Count, MaintenanceIndicatorsService::class, 'countScheduledInspections', 'reports.view_maintenance', false],
            ['communications_failed', 'Comunicações falhadas', IndicatorCategory::Communications, IndicatorValueType::Count, CommunicationIndicatorsService::class, 'countFailedCommunications', null, false],
            ['notifications_unread', 'Notificações não lidas', IndicatorCategory::Communications, IndicatorValueType::Count, CommunicationIndicatorsService::class, 'countUnreadNotifications', null, false],
        ];

        foreach ($definitions as [$code, $name, $category, $valueType, $service, $method, $permission, $sensitive]) {
            $definition = IndicatorDefinition::withTrashed()->firstOrNew(['code' => $code]);
            $definition->forceFill([
                'name' => $name, 'description' => null, 'category' => $category, 'value_type' => $valueType,
                'calculation_service' => $service, 'calculation_method' => $method, 'required_permission' => $permission,
                'is_sensitive' => $sensitive, 'is_active' => true, 'deleted_at' => null,
            ])->save();
        }
    }
}
