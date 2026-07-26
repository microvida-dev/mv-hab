<?php

namespace App\Services\Reporting;

use App\Enums\DashboardType;
use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Enums\ReportSensitivityLevel;
use App\Models\DashboardDefinition;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\Entitlements\MunicipalityEntitlementService;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Database\Eloquent\Builder;

class ReportPermissionService
{
    /** @var list<string> */
    private const APPLICATION_REPORT_CODES = [
        'applications_by_contest',
        'application_status_summary',
    ];

    /** @var array<string, string> */
    private const DOMAIN_EXPORT_PERMISSIONS = [
        'applications_by_contest' => 'applications.export',
        'application_status_summary' => 'applications.export',
        'complaints_summary' => 'complaints.export',
        'housing_occupancy_report' => 'housing_units.export',
    ];

    public function __construct(
        private readonly MunicipalityEntitlementService $entitlements,
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    public function canViewDashboard(User $user, DashboardDefinition $dashboard): bool
    {
        if (! $user->hasPermission('reports.view')) {
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
        if (! $user->hasPermission('reports.view')) {
            return false;
        }

        if (
            $this->isApplicationReport($report)
            && $user->municipality_id === null
            && ! $this->platformScope->hasGlobalScope($user)
        ) {
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
        if (! $this->canExportDefinition($user, $report)) {
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

    public function canExportDefinition(User $user, ReportDefinition $report): bool
    {
        if (! $this->canViewReport($user, $report) || ! $user->hasPermission('reports.export')) {
            return false;
        }

        $domainExportPermission = self::DOMAIN_EXPORT_PERMISSIONS[$report->code] ?? null;
        if ($domainExportPermission !== null && ! $user->hasPermission($domainExportPermission)) {
            return false;
        }

        if (
            $this->isApplicationReport($report)
            && ! $this->entitlements->enabledForUser($user, FeatureKey::ApplicationExport)
        ) {
            return false;
        }

        return true;
    }

    public function isApplicationReport(ReportDefinition $report): bool
    {
        return in_array($report->code, self::APPLICATION_REPORT_CODES, true);
    }

    public function canAccessApplicationExportCatalog(User $user): bool
    {
        return $user->hasPermission('reports.export')
            && $user->hasPermission('applications.export')
            && (
                $user->municipality_id !== null
                || $this->platformScope->hasGlobalScope($user)
            )
            && $this->entitlements->enabledForUser(
                $user,
                FeatureKey::ApplicationExport,
            );
    }

    /**
     * @return list<string>
     */
    public function applicationReportCodes(): array
    {
        return self::APPLICATION_REPORT_CODES;
    }

    public function canManage(User $user): bool
    {
        return $user->hasPermission('reports.manage');
    }

    public function canAudit(User $user): bool
    {
        return $user->hasPermission('reports.audit');
    }

    /**
     * @param  Builder<ReportDefinition>  $query
     * @return Builder<ReportDefinition>
     */
    public function visibleReports(Builder $query, User $user): Builder
    {
        if (! $user->hasPermission('reports.view')) {
            return $query->whereRaw('1 = 0');
        }

        $this->applyRequiredPermissionScope($query, $user);

        if (! $user->hasPermission('reports.view_financial')) {
            $query->where(
                'sensitivity_level',
                '!=',
                ReportSensitivityLevel::HighlySensitive->value,
            );
        }

        if (! $user->hasPermission('reports.view_sensitive')) {
            $query->where(function (Builder $reports) use ($user): void {
                $reports->where(
                    'sensitivity_level',
                    '!=',
                    ReportSensitivityLevel::Sensitive->value,
                );

                if ($user->hasPermission('reports.view_maintenance')) {
                    $reports->orWhere(function (Builder $maintenance): void {
                        $maintenance
                            ->where(
                                'sensitivity_level',
                                ReportSensitivityLevel::Sensitive->value,
                            )
                            ->where(
                                'required_permission',
                                'reports.view_maintenance',
                            );
                    });
                }
            });
        }

        return $query;
    }

    /**
     * @param  Builder<DashboardDefinition>  $query
     * @return Builder<DashboardDefinition>
     */
    public function visibleDashboards(Builder $query, User $user): Builder
    {
        if (! $user->hasPermission('reports.view')) {
            return $query->whereRaw('1 = 0');
        }

        $this->applyRequiredPermissionScope($query, $user);

        return $query
            ->when(
                ! $user->hasPermission('reports.view_executive'),
                fn (Builder $dashboards): Builder => $dashboards->where(
                    'dashboard_type',
                    '!=',
                    DashboardType::Executive->value,
                ),
            )
            ->when(
                ! $user->hasPermission('reports.view_financial'),
                fn (Builder $dashboards): Builder => $dashboards->where(
                    'dashboard_type',
                    '!=',
                    DashboardType::Financial->value,
                ),
            )
            ->when(
                ! $user->hasPermission('reports.view_maintenance'),
                fn (Builder $dashboards): Builder => $dashboards->where(
                    'dashboard_type',
                    '!=',
                    DashboardType::Maintenance->value,
                ),
            );
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function visibleByRequiredPermission(
        Builder $query,
        User $user,
        string $basePermission,
    ): Builder {
        if (! $user->hasPermission($basePermission)) {
            return $query->whereRaw('1 = 0');
        }

        $this->applyRequiredPermissionScope($query, $user);

        return $query;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyRequiredPermissionScope(
        Builder $query,
        User $user,
    ): void {
        $grants = $user->roles()
            ->where('roles.is_active', true)
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();

        if ($grants->contains('*')) {
            return;
        }

        $exact = $grants
            ->reject(fn (string $permission): bool => str_contains(
                $permission,
                '*',
            ))
            ->all();
        $moduleWildcards = $grants
            ->filter(fn (string $permission): bool => str_ends_with(
                $permission,
                '.*',
            ))
            ->map(fn (string $permission): string => substr(
                $permission,
                0,
                -1,
            ))
            ->all();
        $actionWildcards = $grants
            ->filter(fn (string $permission): bool => str_starts_with(
                $permission,
                '*.',
            ))
            ->map(fn (string $permission): string => substr(
                $permission,
                1,
            ))
            ->all();

        $query->where(function (Builder $allowed) use (
            $actionWildcards,
            $exact,
            $moduleWildcards,
        ): void {
            $allowed->whereNull('required_permission');

            if ($exact !== []) {
                $allowed->orWhereIn('required_permission', $exact);
            }

            foreach ($moduleWildcards as $prefix) {
                $allowed->orWhere(
                    'required_permission',
                    'like',
                    $prefix.'%',
                );
            }

            foreach ($actionWildcards as $suffix) {
                $allowed->orWhere(
                    'required_permission',
                    'like',
                    '%'.$suffix,
                );
            }
        });
    }
}
