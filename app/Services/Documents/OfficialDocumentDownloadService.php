<?php

namespace App\Services\Documents;

use App\Models\GeneratedOfficialDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficialDocumentDownloadService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function download(GeneratedOfficialDocument $document, User $actor): StreamedResponse
    {
        $this->audit->record(AuditEvents::ACCESS, $document, 'notifications', 'official_document_download', 'Documento oficial descarregado.');

        return Storage::disk($document->storage_disk)->download($document->storage_path, $document->document_number.'.html');
    }
}
