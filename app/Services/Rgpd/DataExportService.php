<?php

namespace App\Services\Rgpd;

use App\Enums\AccessLogType;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Security\AccessLogService;
use App\Services\Security\SensitiveDataAccessService;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportService
{
    public function __construct(
        private readonly DataInventoryService $inventory,
        private readonly AuditTrailService $audit,
        private readonly AccessLogService $access,
        private readonly SensitiveDataAccessService $sensitiveAccess,
    ) {}

    public function generate(DataSubjectRequest $request, User $actor): DataExportPackage
    {
        $subject = $request->user;
        abort_unless($subject instanceof User, 422, 'Pedido RGPD sem titular associado.');

        $payload = json_encode($this->inventory->collectForUser($subject), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (! is_string($payload)) {
            throw new RuntimeException('Não foi possível serializar a exportação RGPD.');
        }

        $uuid = (string) Str::uuid();
        $filename = 'data-export-'.$uuid.'.json';
        $path = 'rgpd/exports/'.now()->format('Y/m').'/'.$uuid.'/'.$filename;
        Storage::disk('local')->put($path, $payload);

        $package = DataExportPackage::query()->create([
            'data_subject_request_id' => $request->id,
            'user_id' => $subject->id,
            'package_number' => 'EXP-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            'status' => 'generated',
            'format' => 'json',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'filename' => $filename,
            'mime_type' => 'application/json',
            'file_size' => Storage::disk('local')->size($path),
            'checksum' => hash('sha256', $payload),
            'generated_by' => $actor->id,
            'generated_at' => now(),
            'expires_at' => now()->addDays(14),
        ]);

        $this->audit->record('data_export.generated', $package, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Exportação de dados do titular gerada.', subject: $subject, actor: $actor);
        $expiresAt = $package->getAttribute('expires_at');
        $this->audit->record('sensitive_export_created', $package, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Exportação sensível criada em storage privado.', metadata: [
            'format' => $package->format,
            'expires_at' => $expiresAt instanceof DateTimeInterface ? $expiresAt->format(DATE_ATOM) : null,
        ], subject: $subject, actor: $actor);
        $this->audit->record('rgpd_export_requested', $package, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Exportação RGPD gerada após pedido do titular.', subject: $subject, actor: $actor);
        $this->sensitiveAccess->record($actor, $package, 'export', $subject, 'highly_sensitive', 'Exportação RGPD autorizada.');

        return $package;
    }

    public function download(DataExportPackage $package, User $actor): StreamedResponse
    {
        $subject = $package->user;
        abort_unless($subject instanceof User, 422, 'Pacote RGPD sem titular associado.');

        abort_unless(Storage::disk($package->storage_disk)->exists($package->storage_path), 404);

        $package->forceFill(['downloaded_at' => now()])->save();
        $this->access->record(AccessLogType::ExportDownload, $actor, $package, 200);
        $this->sensitiveAccess->record($actor, $package, 'download', $subject, 'highly_sensitive', 'Download de exportação RGPD.');
        $this->audit->record('data_export.downloaded', $package, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Exportação de dados do titular descarregada.', subject: $subject, actor: $actor);
        $this->audit->record('sensitive_export_downloaded', $package, AuditEventCategory::Rgpd, AuditEventSeverity::Warning, 'Download de exportação sensível auditado.', subject: $subject, actor: $actor);

        return Storage::disk($package->storage_disk)->download($package->storage_path, $package->filename, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
