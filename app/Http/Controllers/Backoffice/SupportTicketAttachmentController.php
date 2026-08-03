<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\SupportTicketAttachment;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketAttachmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function download(SupportTicketAttachment $supportTicketAttachment): StreamedResponse
    {
        Gate::authorize('downloadBackoffice', $supportTicketAttachment);

        abort_unless(
            $supportTicketAttachment->storage_disk === 'local',
            404,
        );
        $disk = Storage::disk('local');
        $path = ltrim((string) $supportTicketAttachment->path, '/');
        abort_if(
            $path === ''
            || str_contains($path, '..')
            || ! $disk->exists($path),
            404,
        );
        $this->audit->record(
            AuditEvents::ACCESS,
            $supportTicketAttachment,
            'communications',
            'support_ticket_attachment_downloaded',
            'Anexo privado de pedido de apoio descarregado.',
        );

        return $disk->download(
            $path,
            basename((string) $supportTicketAttachment->original_filename),
        );
    }
}
