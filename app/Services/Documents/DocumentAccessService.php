<?php

namespace App\Services\Documents;

use App\Enums\AccessLogType;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\DocumentAccessAction;
use App\Models\DocumentAccessLog;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Security\AccessLogService;
use App\Services\Security\SensitiveDataAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentAccessService
{
    public function __construct(
        private readonly AuditTrailService $audit,
        private readonly AccessLogService $access,
        private readonly SensitiveDataAccessService $sensitiveAccess,
    ) {}

    public function record(
        DocumentSubmission $submission,
        DocumentAccessAction $action,
        ?DocumentVersion $version = null,
        ?User $actor = null,
    ): DocumentAccessLog {
        $request = $this->request();

        $log = new DocumentAccessLog;
        $log->forceFill([
            'document_submission_id' => $submission->id,
            'document_version_id' => $version?->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
        ]);
        $log->save();

        return $log;
    }

    public function download(DocumentSubmission $submission, User $actor): StreamedResponse
    {
        $version = $submission->currentVersion;
        abort_if($version === null, 404);
        abort_unless(Storage::disk($version->storage_disk)->exists($version->storage_path), 404);

        $this->record($submission, DocumentAccessAction::Download, $version, $actor);
        $this->access->record(AccessLogType::DocumentDownload, $actor, $submission, 200);
        $this->sensitiveAccess->record($actor, $submission, 'download', $submission->user, 'highly_sensitive', 'Download documental autorizado.');
        $this->audit->record('document.downloaded', $submission, AuditEventCategory::Documents, AuditEventSeverity::Warning, 'Documento descarregado.', metadata: [
            'document_version_id' => $version->id,
            'filename' => $version->original_filename,
        ], subject: $submission->user, actor: $actor);

        $stream = Storage::disk($version->storage_disk)->readStream($version->storage_path);
        abort_if($stream === null, 404);

        $disposition = (new ResponseHeaderBag)->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $version->original_filename,
        );

        return response()->stream(static function () use ($stream): void {
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Disposition' => $disposition,
            'Content-Type' => $version->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request();
    }
}
