<?php

namespace App\Services\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupportTicketAttachmentService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(SupportTicket $ticket, User $actor, UploadedFile $file, ?SupportTicketMessage $message = null): SupportTicketAttachment
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js'], true)) {
            throw ValidationException::withMessages(['attachment' => 'Tipo de ficheiro não permitido.']);
        }

        $filename = uniqid('support_', true).'.'.$extension;
        $directory = 'support-tickets/'.$ticket->ticket_number;
        $path = $file->storeAs($directory, $filename, 'local');

        if (! is_string($path)) {
            throw ValidationException::withMessages(['attachment' => 'Não foi possível guardar o anexo.']);
        }

        $contents = Storage::disk('local')->get($path);
        if (! is_string($contents)) {
            throw ValidationException::withMessages(['attachment' => 'Não foi possível ler o anexo guardado.']);
        }

        $attachment = new SupportTicketAttachment([
            'support_ticket_id' => $ticket->id,
            'support_ticket_message_id' => $message?->id,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
            'is_private' => true,
        ]);
        $attachment->forceFill([
            'uploaded_by' => $actor->id,
            'storage_disk' => 'local',
            'path' => $path,
            'checksum' => hash('sha256', $contents),
        ])->save();

        $this->auditLogger->record(AuditEvents::CREATE, $attachment, 'support', 'support_ticket_attachment', 'Anexo privado de ticket criado.', metadata: [
            'ticket_id' => $ticket->id,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
        ]);

        return $attachment->refresh();
    }
}
