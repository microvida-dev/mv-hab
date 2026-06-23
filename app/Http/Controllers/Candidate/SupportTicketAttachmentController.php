<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Models\SupportTicketAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketAttachmentController extends Controller
{
    public function download(SupportTicketAttachment $supportTicketAttachment): StreamedResponse
    {
        Gate::authorize('view', $supportTicketAttachment);

        return Storage::disk((string) $supportTicketAttachment->storage_disk)
            ->download((string) $supportTicketAttachment->path, (string) $supportTicketAttachment->original_filename);
    }
}
