<?php

namespace App\Services\Reporting;

use App\Enums\AccessLogType;
use App\Enums\ReportAccessType;
use App\Enums\ReportExportStatus;
use App\Models\ReportDefinition;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Security\AccessLogService;
use App\Services\Security\SensitiveDataAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReportDownloadService
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly ReportAccessLogger $access,
        private readonly AuditLogger $audit,
        private readonly Request $request,
        private readonly AccessLogService $accessLogs,
        private readonly SensitiveDataAccessService $sensitiveAccess,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function download(ReportExport $export, User $user): StreamedResponse
    {
        $export = DB::transaction(function () use ($export, $user): ReportExport {
            $locked = ReportExport::query()
                ->whereKey($export->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $locked->loadMissing('run.definition');

            $this->assertDownloadable($locked, $user);

            ReportDownloadLog::query()->create([
                'report_export_id' => $locked->getKey(),
                'user_id' => $user->getKey(),
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'downloaded_at' => now(),
            ]);
            $locked->forceFill(['downloaded_at' => now()])->save();

            return $locked;
        });

        $run = $export->getRelationValue('run');
        if (! $run instanceof ReportRun) {
            throw new NotFoundHttpException('A exportação não tem execução associada.');
        }

        $definition = $run->getRelationValue('definition');
        if (! $definition instanceof ReportDefinition) {
            throw new NotFoundHttpException('A exportação não tem relatório associado.');
        }

        $disk = (string) $export->disk;
        $path = ltrim((string) $export->file_path, '/');

        $filtersAttribute = $run->getAttribute('filters');
        $filters = is_array($filtersAttribute) ? $filtersAttribute : [];

        $this->access->record($user, ReportAccessType::DownloadExport, $definition, run: $run, export: $export, filters: $filters, format: $export->format, scope: $export->scope);
        $this->audit->record('report.export.downloaded', $export, 'reports', 'download', 'Exportação descarregada.', metadata: ['file_name' => $export->file_name]);
        if ($export->isTemporalApplicationResultExport()) {
            $this->audit->record(
                'application_result_export_downloaded',
                $export,
                'reports',
                'download',
                'Exportação temporal descarregada.',
                metadata: [
                    'export_public_id' => $export->public_id,
                    'municipality_id' => $export->municipality_id,
                    'contest_id' => $export->contest_id,
                    'mode' => $export->export_mode?->value,
                    'format' => $export->format->value,
                    'scope' => $export->scope->value,
                    'sensitive_fields_included' => $export->sensitive_fields_included,
                    'document_files_included' => $export->document_files_included,
                    'package_sha256' => $export->package_sha256,
                ],
            );
        }
        $this->accessLogs->record(AccessLogType::ExportDownload, $user, $export, 200);
        $this->sensitiveAccess->record($user, $export, 'export', null, 'sensitive', 'Download de exportação de relatório.');

        return Storage::disk($disk)->download(
            $path,
            basename((string) $export->file_name),
        );
    }

    private function assertDownloadable(ReportExport $export, User $user): void
    {
        $run = $export->getRelationValue('run');
        $definition = $run instanceof ReportRun
            ? $run->getRelationValue('definition')
            : null;

        if (! $definition instanceof ReportDefinition) {
            throw new NotFoundHttpException('A exportação não tem relatório associado.');
        }

        if (
            ! $this->permissions->canExport($user, $definition, $export->scope)
            || ! $this->municipalScope->ownsReportExport($user, $export)
            || (
                $export->isTemporalApplicationResultExport()
                && ($export->sensitive_fields_included || $export->document_files_requested)
                && ! $user->hasPermission('reports.export_sensitive')
            )
        ) {
            throw new AuthorizationException;
        }

        $disk = (string) $export->disk;
        $path = ltrim((string) $export->file_path, '/');

        if (
            $export->status !== ReportExportStatus::Completed
            || $disk !== 'local'
            || $path === ''
            || str_contains($path, '..')
            || $export->expires_at?->isPast()
            || ! Storage::disk($disk)->exists($path)
        ) {
            throw new NotFoundHttpException('A exportação expirou ou deixou de estar disponível.');
        }
    }
}
