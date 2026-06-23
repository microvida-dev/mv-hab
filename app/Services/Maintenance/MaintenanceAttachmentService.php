<?php

namespace App\Services\Maintenance;

use App\Enums\MaintenanceAttachmentType;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceAttachmentService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function storeForRequest(MaintenanceRequest $request, UploadedFile $file, User $actor, bool $visibleToTenant = false, ?string $description = null): MaintenanceAttachment
    {
        $storedFilename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');
        $directory = 'maintenance/requests/'.$request->id;
        $path = Storage::disk('local')->putFileAs($directory, $file, $storedFilename);

        $attachment = $request->attachments()->create([
            'uploaded_by' => $actor->id,
            'attachment_type' => str_starts_with((string) $file->getMimeType(), 'image/') ? MaintenanceAttachmentType::Photo : MaintenanceAttachmentType::Document,
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'visible_to_tenant' => $visibleToTenant,
            'description' => $description,
        ]);

        $this->auditLogger->record(AuditEvents::CREATE, $attachment, 'maintenance_requests', 'maintenance_attachment_uploaded', 'Anexo de manutenção carregado.');

        return $attachment;
    }

    public function download(MaintenanceAttachment $attachment, User $actor): StreamedResponse
    {
        abort_unless(Storage::disk($attachment->storage_disk)->exists($attachment->storage_path), 404);

        $this->auditLogger->record(AuditEvents::ACCESS, $attachment, 'maintenance_requests', 'maintenance_attachment_download', 'Anexo de manutenção descarregado.');

        return Storage::disk($attachment->storage_disk)->download(
            $attachment->storage_path,
            'anexo-manutencao-'.$attachment->id.'.'.pathinfo($attachment->original_filename, PATHINFO_EXTENSION),
        );
    }
}
