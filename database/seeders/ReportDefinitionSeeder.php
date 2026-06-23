<?php

namespace Database\Seeders;

use App\Enums\ExportScope;
use App\Enums\ReportFormat;
use App\Enums\ReportSensitivityLevel;
use App\Enums\ReportType;
use App\Models\ReportDefinition;
use App\Services\Reporting\ReportQueryService;
use Illuminate\Database\Seeder;

class ReportDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $baseFormats = [ReportFormat::Html->value, ReportFormat::Csv->value, ReportFormat::Xlsx->value, ReportFormat::Pdf->value];
        $reports = [
            ['applications_by_contest', 'Candidaturas por concurso', 'applicationsByContest', ReportType::Operational, ReportSensitivityLevel::Restricted, null],
            ['application_status_summary', 'Resumo de estados das candidaturas', 'applicationStatusSummary', ReportType::Operational, ReportSensitivityLevel::PublicInternal, null],
            ['eligibility_summary', 'Resumo de elegibilidade', 'eligibilitySummary', ReportType::Operational, ReportSensitivityLevel::Restricted, null],
            ['document_pending_report', 'Pendências documentais', 'documentPending', ReportType::Sensitive, ReportSensitivityLevel::Sensitive, 'reports.view_sensitive'],
            ['complaints_summary', 'Resumo de reclamações', 'complaintsSummary', ReportType::Operational, ReportSensitivityLevel::Restricted, null],
            ['housing_occupancy_report', 'Ocupação do parque habitacional', 'housingOccupancy', ReportType::Executive, ReportSensitivityLevel::PublicInternal, null],
            ['financial_arrears_report', 'Incumprimentos financeiros', 'financialArrears', ReportType::Sensitive, ReportSensitivityLevel::HighlySensitive, 'reports.view_financial'],
            ['maintenance_pending_report', 'Pedidos de manutenção pendentes', 'maintenancePending', ReportType::Operational, ReportSensitivityLevel::Restricted, 'reports.view_maintenance'],
            ['maintenance_costs_by_property', 'Custos de manutenção por imóvel', 'maintenanceCostsByProperty', ReportType::Sensitive, ReportSensitivityLevel::Sensitive, 'reports.view_maintenance'],
            ['executive_summary', 'Resumo executivo', 'executiveSummary', ReportType::Executive, ReportSensitivityLevel::Restricted, 'reports.view_executive'],
        ];

        foreach ($reports as [$code, $name, $method, $type, $sensitivity, $permission]) {
            $report = ReportDefinition::withTrashed()->firstOrNew(['code' => $code]);
            $report->forceFill([
                'name' => $name, 'description' => 'Relatório institucional da plataforma MV HAB.',
                'report_type' => $type, 'sensitivity_level' => $sensitivity, 'required_permission' => $permission,
                'query_service' => ReportQueryService::class, 'query_method' => $method,
                'available_formats' => $baseFormats,
                'available_scopes' => $sensitivity === ReportSensitivityLevel::PublicInternal
                    ? [ExportScope::Aggregated->value]
                    : [ExportScope::Aggregated->value, ExportScope::Pseudonymized->value],
                'filter_schema' => ['date_from', 'date_to', 'program_id', 'contest_id', 'status', 'location'],
                'requires_filters' => in_array($sensitivity, [ReportSensitivityLevel::Sensitive, ReportSensitivityLevel::HighlySensitive], true),
                'is_active' => true, 'deleted_at' => null,
            ])->save();
        }
    }
}
