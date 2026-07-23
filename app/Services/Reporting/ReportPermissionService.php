<?php

namespace App\Services\Reporting;

use App\Enums\DashboardType;
use App\Enums\ExportScope;
use App\Enums\ReportSensitivityLevel;
use App\Models\DashboardDefinition;
use App\Models\ReportDefinition;
use App\Models\User;

class ReportPermissionService
{
    /** @var array<string, string> */
    private const DOMAIN_EXPORT_PERMISSIONS = [
        'applications_by_contest' => 'applications.export',
        'application_status_summary' => 'applications.export',
        'complaints_summary' => 'complaints.export',
        'housing_occupancy_report' => 'housing_units.export',
    ];

    public function canViewDashboard(User $user, DashboardDefinition $dashboard): bool
    {
        if ($user->hasRole('candidate') || ! $user->hasPermission('reports.view')) {
            return false;
        }

        if ($dashboard->required_permission && ! $user->hasPermission($dashboard->required_permission)) {
            return false;
        }

        return match ($dashboard->dashboard_type) {
            DashboardType::Executive => $user->hasPermission('reports.view_executive'),
            DashboardType::Financial => $user->hasPermission('reports.view_financial'),
            DashboardType::Maintenance => $user->hasPermission('reports.view_maintenance'),
            default => true,
        };
    }

    public function canViewReport(User $user, ReportDefinition $report): bool
    {
        if ($user->hasRole('candidate') || ! $user->hasPermission('reports.view')) {
            return false;
        }

        if ($report->required_permission && ! $user->hasPermission($report->required_permission)) {
            return false;
        }

        return match ($report->sensitivity_level) {
            ReportSensitivityLevel::HighlySensitive => $user->hasPermission('reports.view_financial'),
            ReportSensitivityLevel::Sensitive => $report->required_permission === 'reports.view_maintenance'
                ? $user->hasPermission('reports.view_maintenance')
                : $user->hasPermission('reports.view_sensitive'),
            default => true,
        };
    }

    public function canExport(User $user, ReportDefinition $report, ExportScope $scope): bool
    {
        if (! $this->canViewReport($user, $report) || ! $user->hasPermission('reports.export')) {
            return false;
        }

        $domainExportPermission = self::DOMAIN_EXPORT_PERMISSIONS[$report->code] ?? null;
        if ($domainExportPermission !== null && ! $user->hasPermission($domainExportPermission)) {
            return false;
        }

        if ($scope->containsPersonalData() && ! $user->hasPermission('reports.export_nominal')) {
            return false;
        }

        return match ($report->sensitivity_level) {
            ReportSensitivityLevel::HighlySensitive => $user->hasPermission('reports.export_financial'),
            ReportSensitivityLevel::Sensitive => $user->hasPermission('reports.export_sensitive'),
            default => true,
        };
    }

    public function canManage(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.manage');
    }

    public function canAudit(User $user): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission('reports.audit');
    }
}
