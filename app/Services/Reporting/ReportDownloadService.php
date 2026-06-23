<?php

namespace App\Services\Reporting;

use App\Enums\AccessLogType;
use App\Enums\ReportAccessType;
use App\Models\ReportDefinition;
use App\Models\ReportDownloadLog;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Security\AccessLogService;
use App\Services\Security\SensitiveDataAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadService
{
    public function __construct(
        private readonly ReportPermissionService $permissions,
        private readonly ReportAccessLogger $access,
        private readonly AuditLogger $audit,
        private readonly Request $request,
        private readonly AccessLogService $accessLogs,
        private readonly SensitiveDataAccessService $sensitiveAccess,
    ) {}

    public function download(ReportExport $export, User $user): StreamedResponse
    {
        $export->loadMissing('run.definition');
        $run = $export->getRelationValue('run');
        if (! $run instanceof ReportRun) {
            throw new FileNotFoundException('A exportação não tem execução associada.');
        }

        $definition = $run->getRelationValue('definition');
        if (! $definition instanceof ReportDefinition) {
            throw new FileNotFoundException('A exportação não tem relatório associado.');
        }

        if (! $this->permissions->canExport($user, $definition, $export->scope)) {
            throw new AuthorizationException;
        }

        if ($export->expires_at?->isPast() || ! Storage::disk($export->disk)->exists($export->file_path)) {
            throw new FileNotFoundException('A exportação expirou ou deixou de estar disponível.');
        }

        ReportDownloadLog::query()->create([
            'report_export_id' => $export->getKey(),
            'user_id' => $user->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'downloaded_at' => now(),
        ]);
        $export->forceFill(['downloaded_at' => now()])->save();

        $filtersAttribute = $run->getAttribute('filters');
        $filters = is_array($filtersAttribute) ? $filtersAttribute : [];

        $this->access->record($user, ReportAccessType::DownloadExport, $definition, run: $run, export: $export, filters: $filters, format: $export->format, scope: $export->scope);
        $this->audit->record('report.export.downloaded', $export, 'reports', 'download', 'Exportação descarregada.', metadata: ['file_name' => $export->file_name]);
        $this->accessLogs->record(AccessLogType::ExportDownload, $user, $export, 200);
        $this->sensitiveAccess->record($user, $export, 'export', null, 'sensitive', 'Download de exportação de relatório.');

        return Storage::disk($export->disk)->download($export->file_path, $export->file_name);
    }
}
