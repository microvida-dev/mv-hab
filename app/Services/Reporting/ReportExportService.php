<?php

namespace App\Services\Reporting;

use App\Enums\ExportScope;
use App\Enums\ReportAccessType;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Reporting\Exporters\CsvReportExporter;
use App\Services\Reporting\Exporters\HtmlReportExporter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExportService
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly ReportRunService $runs,
        private readonly CsvReportExporter $csv,
        private readonly HtmlReportExporter $html,
        private readonly ReportAccessLogger $access,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(
        ReportDefinition $definition,
        User $user,
        array $filters,
        ReportFormat $requested,
        ExportScope $scope,
        bool $confirmed,
    ): ReportExport {
        if (! $this->permissions->canExport($user, $definition, $scope)) {
            throw new AuthorizationException;
        }

        $sensitivity = $definition->sensitivity_level;
        if ($sensitivity?->requiresConfirmation() && ! $confirmed) {
            throw new AuthorizationException(
                'A exportação sensível exige confirmação explícita.'
            );
        }

        $actual = match ($requested) {
            ReportFormat::Xlsx => ReportFormat::Csv,
            ReportFormat::Pdf => ReportFormat::Html,
            default => $requested,
        };

        $result = $this->runs->run(
            $definition,
            $user,
            $filters,
            $actual,
            $scope,
        );

        $run = $result->reportRun;
        $rows = $result->rows;
        $filtersAttribute = $run->getAttribute('filters');
        $runFilters = is_array($filtersAttribute) ? $filtersAttribute : [];

        $contents = $actual === ReportFormat::Csv
            ? $this->csv->render($rows)
            : $this->html->render(
                $definition->name,
                $rows,
                $runFilters,
            );

        $extension = $actual === ReportFormat::Csv ? 'csv' : 'html';
        $uuid = (string) Str::uuid();
        $safeCode = Str::slug($definition->code);
        $fileName = $safeCode.'-'.now()->format('Ymd-His').'.'.$extension;
        $path = 'reports/'.now()->format('Y/m').'/'.$uuid.'/'.$fileName;

        Storage::disk('local')->put($path, $contents);

        $export = new ReportExport;

        $export->forceFill([
            'public_id' => $uuid,
            'report_run_id' => $run->getKey(),
            'user_id' => $user->getKey(),
            'status' => ReportExportStatus::Completed,
            'requested_format' => $requested,
            'format' => $actual,
            'scope' => $scope,
            'disk' => 'local',
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => Storage::disk('local')->size($path),
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ])->save();

        $this->access->record(
            $user,
            ReportAccessType::ExportReport,
            $definition,
            run: $run,
            export: $export,
            filters: $runFilters,
            format: $actual,
            scope: $scope,
        );

        $this->audit->record(
            'report.export.completed',
            $export,
            'reports',
            'export',
            'Exportação de relatório concluída.',
            metadata: [
                'report_code' => $definition->code,
                'requested_format' => $requested->value,
                'actual_format' => $actual->value,
                'scope' => $scope->value,
                'filters' => $runFilters,
            ],
        );

        return $export->refresh();
    }
}
