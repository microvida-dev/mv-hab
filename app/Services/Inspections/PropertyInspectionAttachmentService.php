<?php

namespace App\Services\Inspections;

use App\Enums\MaintenanceAttachmentType;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionAttachment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PropertyInspectionAttachmentService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(PropertyInspection $inspection, UploadedFile $file, User $actor, bool $visibleToTenant = false, ?int $itemId = null): PropertyInspectionAttachment
    {
        $storedFilename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = Storage::disk('local')->putFileAs('inspections/'.$inspection->id, $file, $storedFilename);

        $attachment = $inspection->attachments()->create([
            'property_inspection_item_id' => $itemId,
            'uploaded_by' => $actor->id,
            'attachment_type' => str_starts_with((string) $file->getMimeType(), 'image/') ? MaintenanceAttachmentType::Photo : MaintenanceAttachmentType::Document,
            'original_filename' => $file->getClientOriginalName(),
            'storage_disk' => 'local',
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize() ?: 0,
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'visible_to_tenant' => $visibleToTenant,
        ]);

        $this->auditLogger->record(AuditEvents::CREATE, $attachment, 'inspections', 'inspection_attachment_uploaded', 'Anexo de vistoria carregado.');

        return $attachment;
    }

    public function download(PropertyInspectionAttachment $attachment, User $actor): StreamedResponse
    {
        abort_unless(Storage::disk($attachment->storage_disk)->exists($attachment->storage_path), 404);

        $this->auditLogger->record(AuditEvents::ACCESS, $attachment, 'inspections', 'inspection_attachment_download', 'Anexo de vistoria descarregado.');

        return Storage::disk($attachment->storage_disk)->download(
            $attachment->storage_path,
            'anexo-vistoria-'.$attachment->id.'.'.pathinfo($attachment->original_filename, PATHINFO_EXTENSION),
        );
    }
}
