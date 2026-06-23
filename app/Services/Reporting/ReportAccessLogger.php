<?php

namespace App\Services\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportAccessType;
use App\Enums\ReportFormat;
use App\Models\DashboardDefinition;
use App\Models\ReportAccessLog;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use Illuminate\Http\Request;

class ReportAccessLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function record(
        User $user,
        ReportAccessType $type,
        ?ReportDefinition $report = null,
        ?DashboardDefinition $dashboard = null,
        ?ReportRun $run = null,
        ?ReportExport $export = null,
        array $filters = [],
        ?ReportFormat $format = null,
        ?ExportScope $scope = null,
    ): ReportAccessLog {
        return ReportAccessLog::query()->create([
            'user_id' => $user->getKey(),
            'report_definition_id' => $report?->getKey(),
            'dashboard_definition_id' => $dashboard?->getKey(),
            'report_run_id' => $run?->getKey(),
            'report_export_id' => $export?->getKey(),
            'access_type' => $type,
            'format' => $format,
            'scope' => $scope,
            'filters' => $filters ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'accessed_at' => now(),
        ]);
    }
}
