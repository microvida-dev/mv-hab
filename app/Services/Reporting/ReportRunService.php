<?php

namespace App\Services\Reporting;

use App\Data\Reports\ReportRunResult;
use App\Enums\ExportScope;
use App\Enums\ReportAccessType;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Models\ReportDefinition;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReportRunService
{
    public function __construct(
        private readonly ReportFilterService $filters,
        private readonly ReportQueryRegistry $queries,
        private readonly ReportPermissionService $permissions,
        private readonly ReportAccessLogger $access,
        private readonly SensitiveDataMaskingService $masking,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(
        ReportDefinition $definition,
        User $user,
        array $filters,
        ReportFormat $format = ReportFormat::Html,
        ExportScope $scope = ExportScope::Aggregated,
    ): ReportRunResult {
        if (! $this->permissions->canViewReport($user, $definition)) {
            throw new AuthorizationException;
        }

        $normalized = $this->filters->normalize($filters);

        if (
            ! $user->hasRole('administrator')
            && $definition->requires_filters
            && $normalized === []
        ) {
            throw new AuthorizationException(
                'Este relatório exige pelo menos um filtro.'
            );
        }

        $run = new ReportRun;

        $run->forceFill([
            'public_id' => (string) Str::uuid(),
            'report_definition_id' => $definition->getKey(),
            'user_id' => $user->getKey(),
            'status' => ReportRunStatus::Started,
            'format' => $format,
            'scope' => $scope,
            'filters' => $normalized,
            'started_at' => now(),
        ])->save();

        try {
            $rows = $this->masking->maskRows(
                $this->queries->execute($definition, $normalized),
                $scope,
            );

            $run->forceFill([
                'status' => ReportRunStatus::Completed,
                'row_count' => count($rows),
                'completed_at' => now(),
            ])->save();

            $this->access->record(
                $user,
                ReportAccessType::RunReport,
                $definition,
                run: $run,
                filters: $normalized,
                format: $format,
                scope: $scope,
            );

            $this->audit->record(
                'report.run.completed',
                $run,
                'reports',
                'run',
                'Relatório executado.',
                metadata: [
                    'report_code' => $definition->code,
                    'filters' => $normalized,
                    'format' => $format->value,
                    'scope' => $scope->value,
                    'row_count' => count($rows),
                ],
            );

            return new ReportRunResult(
                reportRun: $run->refresh(),
                rows: $rows,
                metadata: [],
            );
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => ReportRunStatus::Failed,
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000,
                ),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function replay(ReportRun $run, User $user): array
    {
        $definition = $run->getRelationValue('definition');
        if (! $definition instanceof ReportDefinition) {
            throw new RuntimeException('Execução de relatório sem definição associada.');
        }

        if (! $this->permissions->canViewReport($user, $definition)) {
            throw new AuthorizationException;
        }

        $filtersAttribute = $run->getAttribute('filters');
        $filters = is_array($filtersAttribute) ? $filtersAttribute : [];
        $scope = $run->getAttribute('scope');
        if (! $scope instanceof ExportScope) {
            throw new RuntimeException('Execução de relatório sem âmbito válido.');
        }

        return $this->masking->maskRows(
            $this->queries->execute(
                $definition,
                $filters,
            ),
            $scope,
        );
    }
}
